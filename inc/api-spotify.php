<?php
// =========================================================================
// 🎵 SPOTIFY, DEEZER E ITUNES API LÓGICA
// =========================================================================

function tiete_get_spotify_token() {
    $cached = get_transient('tiete_spotify_token');
    if ($cached) return $cached;

    if (!defined('SPOTIFY_CLIENT_ID') || !defined('SPOTIFY_CLIENT_SECRET')) return null;

    $response = wp_remote_post('https://accounts.spotify.com/api/token', [
        'body'    => ['grant_type' => 'client_credentials'],
        'headers' => [
            'Authorization' => 'Basic ' . base64_encode(SPOTIFY_CLIENT_ID . ':' . SPOTIFY_CLIENT_SECRET),
            'Content-Type'  => 'application/x-www-form-urlencoded',
        ],
        'timeout' => 10,
    ]);

    if (is_wp_error($response)) return null;

    $body = json_decode(wp_remote_retrieve_body($response), true);
    if (empty($body['access_token'])) return null;

    set_transient('tiete_spotify_token', $body['access_token'], 3300); // 55 min
    return $body['access_token'];
}

add_action('wp_ajax_spotify_buscar_album', 'tiete_ajax_spotify_buscar_album');
function tiete_ajax_spotify_buscar_album() {
    check_ajax_referer('spotify_buscar_album', 'nonce');
    if (!current_user_can('edit_posts')) { wp_send_json_error('Permissão negada.'); return; }

    $input = sanitize_text_field($_POST['album_id'] ?? '');
    if (!$input) { wp_send_json_error('URL ou ID inválido.'); return; }

    if (str_contains($input, 'spotify.com/album/')) {
        preg_match('/album\/([A-Za-z0-9]+)/', $input, $m);
        $album_id = $m[1] ?? '';
    } else {
        $album_id = preg_replace('/[^A-Za-z0-9]/', '', $input);
    }
    if (!$album_id) { wp_send_json_error('Não foi possível extrair o ID do álbum.'); return; }

    $token = tiete_get_spotify_token();
    if (!$token) { wp_send_json_error('Credenciais do Spotify não configuradas ou inválidas.'); return; }

    $resp = wp_remote_get("https://api.spotify.com/v1/albums/{$album_id}", [
        'headers' => ['Authorization' => "Bearer {$token}"],
        'timeout' => 10,
    ]);
    if (is_wp_error($resp)) { wp_send_json_error('Erro de conexão com o Spotify.'); return; }

    $album = json_decode(wp_remote_retrieve_body($resp), true);
    if (empty($album['id'])) { wp_send_json_error('Álbum não encontrado. Verifique a URL.'); return; }

    $tracks    = $album['tracks']['items'] ?? [];
    $tracklist = array_map(fn($t) => $t['name'], $tracks);
    $preview_url    = '';
    $faixa_destaque = '';
    $artistas = implode(', ', array_map(fn($a) => $a['name'], $album['artists'] ?? []));

    $generos = implode(', ', $album['genres'] ?? []);
    if (!$generos && !empty($album['artists'][0]['id'])) {
        $artist_id   = $album['artists'][0]['id'];
        $resp_artist = wp_remote_get("https://api.spotify.com/v1/artists/{$artist_id}", [
            'headers' => ['Authorization' => "Bearer {$token}"],
            'timeout' => 10,
        ]);
        if (!is_wp_error($resp_artist)) {
            $artist  = json_decode(wp_remote_retrieve_body($resp_artist), true);
            $generos = implode(', ', $artist['genres'] ?? []);
        }
    }

    $cover_url = $album['images'][0]['url'] ?? '';
    $faixa_hint = sanitize_text_field($_POST['faixa_hint'] ?? '');
    $itunes_debug = '';

    if (!$preview_url) {
        [$preview_url, $faixa_destaque, $itunes_debug] = tiete_get_preview_deezer($artistas, $album['name'], $faixa_hint);
    }

    if (!$preview_url) {
        [$preview_url, $faixa_destaque, $itunes_debug] = tiete_get_preview_itunes($artistas, $album['name'], $faixa_hint);
    }

    wp_send_json_success([
        'titulo'          => $album['name'],
        'artista'         => $artistas,
        'ano'             => substr($album['release_date'] ?? '', 0, 4),
        'genero'          => $generos,
        'cover_url'       => $cover_url,
        'preview_url'     => $preview_url,
        'faixa_destaque'  => $faixa_destaque,
        'tracklist'       => $tracklist,
        'preview_source'  => $preview_url
            ? (str_contains($preview_url, 'deezer') || str_contains($preview_url, 'dzcdn') ? 'deezer'
              : (str_contains($preview_url, 'itunes') || str_contains($preview_url, 'apple') ? 'itunes'
              : 'spotify'))
            : '',
        'debug'           => $itunes_debug,
    ]);
}

function tiete_normalizar_faixa($s) {
    return preg_replace('/[^a-z0-9]/', '', strtolower($s));
}

function tiete_get_preview_deezer($artista, $album_nome, $faixa_hint = '') {
    $args = ['timeout' => 15, 'sslverify' => false, 'headers' => ['User-Agent' => 'Mozilla/5.0']];
    $query = urlencode(trim($artista . ' ' . $album_nome));
    $resp  = wp_remote_get("https://api.deezer.com/search/album?q={$query}&limit=5", $args);

    if (is_wp_error($resp)) return ['', '', 'Deezer erro de conexão: ' . $resp->get_error_message()];

    $albums = json_decode(wp_remote_retrieve_body($resp), true)['data'] ?? [];
    if (!$albums) return ['', '', 'Deezer: álbum não encontrado'];

    $album_id = $albums[0]['id'];
    $resp2    = wp_remote_get("https://api.deezer.com/album/{$album_id}/tracks?limit=50", $args);

    if (is_wp_error($resp2)) return ['', '', 'Deezer tracks erro: ' . $resp2->get_error_message()];

    $tracks = json_decode(wp_remote_retrieve_body($resp2), true)['data'] ?? [];
    if (!$tracks) return ['', '', "Deezer: nenhuma faixa encontrada"];

    if ($faixa_hint) {
        $hint_norm = tiete_normalizar_faixa($faixa_hint);
        foreach ($tracks as $t) {
            $titulo_norm = tiete_normalizar_faixa($t['title'] ?? '');
            $match = $titulo_norm && $hint_norm && (str_contains($titulo_norm, $hint_norm) || str_contains($hint_norm, $titulo_norm));
            if ($match && !empty($t['preview'])) return [$t['preview'], $t['title'], ''];
        }
    }

    usort($tracks, fn($a, $b) => ($b['rank'] ?? 0) - ($a['rank'] ?? 0));
    foreach ($tracks as $t) {
        if (!empty($t['preview'])) return [$t['preview'], $t['title'] ?? '', ''];
    }

    return ['', '', 'Deezer: ' . count($tracks) . ' faixas sem preview'];
}

function tiete_get_preview_itunes($artista, $album_nome, $faixa_hint = '') {
    $args = ['timeout' => 15, 'sslverify' => false, 'headers' => ['User-Agent' => 'Mozilla/5.0']];
    $query = urlencode(trim($artista . ' ' . $album_nome));

    foreach (['br', 'us'] as $country) {
        $url  = "https://itunes.apple.com/search?term={$query}&entity=song&country={$country}&limit=25";
        $resp = wp_remote_get($url, $args);

        if (is_wp_error($resp)) return ['', '', "iTunes erro de conexão ({$country}): " . $resp->get_error_message()];

        $tracks = json_decode(wp_remote_retrieve_body($resp), true)['results'] ?? [];

        if ($faixa_hint) {
            $hint_norm = tiete_normalizar_faixa($faixa_hint);
            foreach ($tracks as $t) {
                if (($t['wrapperType'] ?? '') !== 'track' || empty($t['previewUrl'])) continue;
                $track_norm = tiete_normalizar_faixa($t['trackName'] ?? '');
                if ($track_norm && $hint_norm && (str_contains($track_norm, $hint_norm) || str_contains($hint_norm, $track_norm))) {
                    return [$t['previewUrl'], $t['trackName'] ?? '', ''];
                }
            }
        }

        foreach ($tracks as $t) {
            if (!empty($t['previewUrl'])) return [$t['previewUrl'], $t['trackName'] ?? '', ''];
        }
        $debug_country = "iTunes {$country}: " . count($tracks) . " faixas, nenhuma com preview";
    }

    return ['', '', $debug_country ?? 'iTunes: sem resultados'];
}

function tiete_importar_capa_spotify($url, $post_id, $titulo) {
    require_once ABSPATH . 'wp-admin/includes/media.php';
    require_once ABSPATH . 'wp-admin/includes/file.php';
    require_once ABSPATH . 'wp-admin/includes/image.php';

    $tmp = download_url($url, 15);
    if (is_wp_error($tmp)) return false;

    $file = [
        'name'     => sanitize_file_name($titulo ?: 'album-cover') . '.jpg',
        'tmp_name' => $tmp,
    ];

    $attachment_id = media_handle_sideload($file, $post_id);
    @unlink($tmp);

    if (is_wp_error($attachment_id)) return false;

    set_post_thumbnail($post_id, $attachment_id);
    return true;
}