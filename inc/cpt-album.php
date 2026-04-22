<?php
// =========================================================================
// 🎵 ALBUM DA SEMANA — CPT, META BOX E AJAX DO FRONT-END
// =========================================================================

add_action('init', 'tiete_register_album_cpt');
function tiete_register_album_cpt() {
    register_post_type('album-semana', [
        'labels' => [
            'name'          => 'Álbuns da Semana',
            'singular_name' => 'Álbum da Semana',
            'add_new_item'  => 'Adicionar Álbum',
            'edit_item'     => 'Editar Álbum',
            'all_items'     => 'Todos os Álbuns',
        ],
        'public'       => false,
        'show_ui'      => true,
        'show_in_menu' => true,
        'supports'     => ['title', 'editor', 'thumbnail'],
        'menu_icon'    => 'dashicons-album',
    ]);
}

function tiete_get_arquivo_leve() {
    $q = new WP_Query([
        'post_type'     => 'album-semana',
        'posts_per_page'=> -1,
        'orderby'       => 'date',
        'order'         => 'DESC',
        'no_found_rows' => true,
    ]);
    $lista = [];
    while ($q->have_posts()) {
        $q->the_post();
        $id      = get_the_ID();
        $lista[] = [
            'id'       => $id,
            'titulo'   => get_the_title(),
            'artista'  => get_post_meta($id, 'album_artista', true),
            'ano'      => get_post_meta($id, 'album_ano',     true),
            'thumb_url'=> get_the_post_thumbnail_url($id, 'thumbnail') ?: get_post_meta($id, 'spotify_cover_url', true) ?: '',
        ];
    }
    wp_reset_postdata();
    return $lista;
}

add_action('add_meta_boxes', 'tiete_adicionar_meta_box_album');
function tiete_adicionar_meta_box_album() {
    add_meta_box('album_dados_extra', '🎵 Dados do Álbum', 'tiete_renderizar_meta_box_album', 'album-semana', 'normal', 'high');
}

function tiete_renderizar_meta_box_album($post) {
    wp_nonce_field('salvar_album_nonce', 'album_nonce_campo');
    $artista          = get_post_meta($post->ID, 'album_artista',         true);
    $ano              = get_post_meta($post->ID, 'album_ano',             true);
    $genero           = get_post_meta($post->ID, 'album_genero',          true);
    $audio_url        = get_post_meta($post->ID, 'album_audio_url',       true);
    $faixa_destaque   = get_post_meta($post->ID, 'album_faixa_destaque',  true);
    $tracklist        = get_post_meta($post->ID, 'album_tracklist',       true);
    $streaming_links  = get_post_meta($post->ID, 'album_streaming_links', true);
    $spotify_cover    = get_post_meta($post->ID, 'spotify_cover_url',     true);
    $has_thumbnail    = has_post_thumbnail($post->ID);
    $cover_preview    = $has_thumbnail ? get_the_post_thumbnail_url($post->ID, 'thumbnail') : $spotify_cover;
    ?>

    <div style="background:#f0f6fc; border:1px solid #c3d9f0; border-radius:4px; padding:14px 16px; margin-bottom:18px;">
        <strong style="display:block; margin-bottom:8px;">🔍 Buscar dados no Spotify</strong>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="spotify_album_input" placeholder="Cole a URL do álbum no Spotify" style="flex:1; min-width:260px; padding:6px 8px;">
            <button type="button" class="button button-primary" onclick="tieteSpotifyBuscar()">Buscar ▸</button>
        </div>
        <p id="spotify_status" style="margin:8px 0 0; font-size:12px; color:#555;"></p>
        <?php wp_nonce_field('spotify_buscar_album', 'spotify_nonce_campo'); ?>
        <input type="hidden" id="spotify_cover_url" name="spotify_cover_url" value="<?php echo esc_url($spotify_cover); ?>">
    </div>

    <?php if ($cover_preview) : ?>
    <div style="margin-bottom:16px; display:flex; align-items:center; gap:12px;">
        <img id="spotify_cover_preview" src="<?php echo esc_url($cover_preview); ?>" style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
        <small style="color:#888;"><?php echo $has_thumbnail ? 'Imagem destacada (WordPress)' : 'Capa do Spotify — será importada ao salvar'; ?></small>
    </div>
    <?php else : ?>
    <img id="spotify_cover_preview" src="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; display:none; margin-bottom:16px;">
    <?php endif; ?>

    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:15px;">
        <div style="flex:1; min-width:180px;">
            <label for="album_artista" style="display:block; margin-bottom:4px;"><strong>Artista:</strong></label>
            <input type="text" id="album_artista" name="album_artista" value="<?php echo esc_attr($artista); ?>" style="width:100%; padding:5px;">
        </div>
        <div style="flex:0 0 100px;">
            <label for="album_ano" style="display:block; margin-bottom:4px;"><strong>Ano:</strong></label>
            <input type="text" id="album_ano" name="album_ano" value="<?php echo esc_attr($ano); ?>" style="width:100%; padding:5px;">
        </div>
        <div style="flex:1; min-width:180px;">
            <label for="album_genero" style="display:block; margin-bottom:4px;"><strong>Gênero:</strong></label>
            <input type="text" id="album_genero" name="album_genero" value="<?php echo esc_attr($genero); ?>" style="width:100%; padding:5px;">
        </div>
    </div>

    <hr style="margin:15px 0;">

    <div style="margin-bottom:15px;">
        <label style="display:block; margin-bottom:6px;"><strong>Preview de áudio</strong> <small>(URL gerada pelo Spotify — 30s, já licenciado)</small>:</label>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <input type="text" id="album_audio_url" name="album_audio_url" value="<?php echo esc_url($audio_url); ?>" style="flex:1; min-width:200px; padding:5px;" placeholder="Preenchido automaticamente pelo Buscar ▸">
            <?php if ($audio_url) : ?>
                <audio controls src="<?php echo esc_url($audio_url); ?>" style="max-width:200px;"></audio>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-bottom:15px;">
        <label for="album_faixa_destaque" style="display:block; margin-bottom:4px;"><strong>Faixa Recomendada (nome):</strong></label>
        <input type="text" id="album_faixa_destaque" name="album_faixa_destaque" value="<?php echo esc_attr($faixa_destaque); ?>" style="width:100%; max-width:400px; padding:5px;" placeholder="ex: Track 3 - Nome da Música">
    </div>

    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <label for="album_tracklist" style="display:block; margin-bottom:4px;"><strong>Tracklist</strong> <small>(uma faixa por linha)</small>:</label>
            <textarea id="album_tracklist" name="album_tracklist" rows="8" style="width:100%; padding:5px;"><?php echo esc_textarea($tracklist); ?></textarea>
        </div>
        <div style="flex:1; min-width:200px;">
            <label for="album_streaming_links" style="display:block; margin-bottom:4px;"><strong>Links de Streaming</strong> <small>(formato: <code>Plataforma|https://url</code> por linha)</small>:</label>
            <textarea id="album_streaming_links" name="album_streaming_links" rows="8" style="width:100%; padding:5px;" placeholder="Spotify|https://open.spotify.com/...&#10;Apple Music|https://music.apple.com/..."><?php echo esc_textarea($streaming_links); ?></textarea>
        </div>
    </div>

    <script>
    async function tieteSpotifyBuscar() {
        var input   = document.getElementById('spotify_album_input').value.trim();
        var status  = document.getElementById('spotify_status');
        var nonce   = document.getElementById('spotify_nonce_campo').value;
        var post_id = document.getElementById('post_ID') ? document.getElementById('post_ID').value : 0;

        if (!input) { status.textContent = 'Cole a URL do álbum no Spotify.'; return; }

        status.style.color = '#555';
        status.textContent = 'Buscando...';

        var faixaHint = (document.getElementById('album_faixa_destaque') || {}).value || '';

        var body = new FormData();
        body.append('action',      'spotify_buscar_album');
        body.append('nonce',       nonce);
        body.append('album_id',    input);
        body.append('post_id',     post_id);
        body.append('faixa_hint',  faixaHint);

        try {
            var r    = await fetch(ajaxurl, { method: 'POST', body: body });
            var json = await r.json();

            if (!json.success) {
                status.style.color = '#c00';
                status.textContent = '✗ ' + (json.data || 'Erro ao buscar álbum.');
                return;
            }

            var d = json.data;

            var titleInput = document.getElementById('title');
            if (titleInput && d.titulo) titleInput.value = d.titulo;

            if (d.artista)        document.getElementById('album_artista').value        = d.artista;
            if (d.ano)            document.getElementById('album_ano').value             = d.ano;
            if (d.genero)         document.getElementById('album_genero').value          = d.genero;
            if (d.preview_url)    document.getElementById('album_audio_url').value       = d.preview_url;
            if (d.faixa_destaque) document.getElementById('album_faixa_destaque').value  = d.faixa_destaque;
            if (d.tracklist)      document.getElementById('album_tracklist').value        = d.tracklist.join('\n');

            if (d.cover_url) {
                document.getElementById('spotify_cover_url').value = d.cover_url;
                var prev = document.getElementById('spotify_cover_preview');
                prev.src = d.cover_url;
                prev.style.display = 'block';
            }

            status.style.color = '#1a7a1a';
            if (d.preview_url) {
                var fonte = d.preview_source === 'itunes' ? 'iTunes' : 'Spotify';
                status.textContent = '✓ Preenchido! Preview via ' + fonte + ': "' + (d.faixa_destaque || 'faixa selecionada') + '". Escreva o review e salve.';
            } else {
                status.style.color = '#b36b00';
                status.textContent = '⚠ Sem preview. Diagnóstico: ' + (d.debug || 'sem detalhes');
            }
        } catch(e) {
            status.style.color = '#c00';
            status.textContent = '✗ Erro de conexão.';
        }
    }
    </script>
    <?php
}

add_action('save_post', 'tiete_salvar_album');
function tiete_salvar_album($post_id) {
    if (!isset($_POST['album_nonce_campo']) || !wp_verify_nonce($_POST['album_nonce_campo'], 'salvar_album_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (get_post_type($post_id) !== 'album-semana') return;
    if (!current_user_can('edit_post', $post_id)) return;

    foreach (['album_artista', 'album_ano', 'album_genero', 'album_faixa_destaque'] as $campo) {
        if (isset($_POST[$campo])) update_post_meta($post_id, $campo, sanitize_text_field($_POST[$campo]));
    }
    if (isset($_POST['album_audio_url']))       update_post_meta($post_id, 'album_audio_url',       esc_url_raw($_POST['album_audio_url']));
    if (isset($_POST['album_tracklist']))       update_post_meta($post_id, 'album_tracklist',       sanitize_textarea_field($_POST['album_tracklist']));
    if (isset($_POST['album_streaming_links'])) update_post_meta($post_id, 'album_streaming_links', sanitize_textarea_field($_POST['album_streaming_links']));

    $spotify_cover = isset($_POST['spotify_cover_url']) ? esc_url_raw($_POST['spotify_cover_url']) : '';
    update_post_meta($post_id, 'spotify_cover_url', $spotify_cover);

    if ($spotify_cover && !has_post_thumbnail($post_id) && function_exists('tiete_importar_capa_spotify')) {
        tiete_importar_capa_spotify($spotify_cover, $post_id, get_post_field('post_title', $post_id));
    }
}

// AJAX FRONT-END
add_action('wp_ajax_get_album_semana',        'tiete_ajax_get_album');
add_action('wp_ajax_nopriv_get_album_semana', 'tiete_ajax_get_album');
function tiete_ajax_get_album() {
    $id   = intval($_GET['id'] ?? 0);
    $post = $id ? get_post($id) : null;
    if (!$post || $post->post_type !== 'album-semana') { wp_send_json_error(); return; }

    $tracklist_raw = get_post_meta($id, 'album_tracklist', true);
    $tracklist     = $tracklist_raw ? array_values(array_filter(array_map('trim', explode("\n", $tracklist_raw)))) : [];

    $links_raw = get_post_meta($id, 'album_streaming_links', true);
    $streaming = [];
    if ($links_raw) {
        foreach (explode("\n", $links_raw) as $linha) {
            $partes = explode('|', trim($linha), 2);
            if (count($partes) === 2 && !empty($partes[1])) {
                $streaming[] = ['name' => sanitize_text_field($partes[0]), 'url' => esc_url($partes[1])];
            }
        }
    }

    wp_send_json_success([
        'id'              => $id,
        'titulo'          => get_the_title($id),
        'artista'         => get_post_meta($id, 'album_artista',        true),
        'ano'             => get_post_meta($id, 'album_ano',            true),
        'genero'          => get_post_meta($id, 'album_genero',         true),
        'cover_url'       => get_the_post_thumbnail_url($id, 'large') ?: get_post_meta($id, 'spotify_cover_url', true) ?: '',
        'audio_url'       => get_post_meta($id, 'album_audio_url',      true),
        'review_html'     => apply_filters('the_content', $post->post_content),
        'faixa_destaque'  => get_post_meta($id, 'album_faixa_destaque', true),
        'tracklist'       => $tracklist,
        'streaming_links' => $streaming,
    ]);
}