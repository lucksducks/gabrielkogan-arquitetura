<?php
// Habilita o suporte a Imagens Destacadas no Tema
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' ); // Boa prática — deixa o WP gerenciar o <title>
    add_image_size( 'preview-lateral', 800, 800, true );
});
// =========================================================================
// TRADUÇÃO: CAIXA DE CONTEÚDO EM INGLÊS (CUSTOM META BOX)
// =========================================================================

add_action( 'add_meta_boxes', 'adicionar_caixa_dados_projeto' );
function adicionar_caixa_dados_projeto() {
    add_meta_box( 
        'caixa_dados_projeto', 
        'Dados Adicionais e Tradução (EN)', 
        'renderizar_caixa_dados_projeto', 
        'post', 
        'normal', 
        'high' 
    );
}

function renderizar_caixa_dados_projeto( $post ) {
    wp_nonce_field( 'salvar_dados_projeto_nonce', 'dados_projeto_nonce_campo' );

    $autoria_pt = get_post_meta( $post->ID, 'autoria_pt', true );
    $autoria_en = get_post_meta( $post->ID, 'autoria_en', true );
    $titulo_en = get_post_meta( $post->ID, 'titulo_en', true );
    $texto_en = get_post_meta( $post->ID, 'texto_en', true );

    echo '<div style="display:flex; gap: 20px; margin-bottom: 20px;">';
        echo '<div style="flex: 1;">';
        echo '<label for="autoria_pt" style="display:block; margin-bottom:5px;"><strong>Autoria / Ano (PT):</strong><br><small>Use Enter para quebrar linha</small></label>';
        echo '<textarea id="autoria_pt" name="autoria_pt" rows="3" style="width:100%; padding:5px;">' . esc_textarea( $autoria_pt ) . '</textarea>';
        echo '</div>';

        echo '<div style="flex: 1;">';
        echo '<label for="autoria_en" style="display:block; margin-bottom:5px;"><strong>Autoria / Ano (EN):</strong><br><small>Use Enter para quebrar linha</small></label>';
        echo '<textarea id="autoria_en" name="autoria_en" rows="3" style="width:100%; padding:5px;">' . esc_textarea( $autoria_en ) . '</textarea>';
        echo '</div>';
    echo '</div>';

    echo '<hr style="margin: 20px 0;">';

    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="titulo_en" style="display:block; margin-bottom:5px;"><strong>Título do Projeto em Inglês:</strong></label>';
    echo '<input type="text" id="titulo_en" name="titulo_en" value="' . esc_attr( $titulo_en ) . '" style="width:100%; max-width:400px; padding:5px;">';
    echo '</div>';

    echo '<div>';
    echo '<label style="display:block; margin-bottom:10px;"><strong>Texto em Inglês (Ficha Técnica / Descrição):</strong></label>';
    wp_editor( $texto_en, 'texto_en', array(
        'textarea_name' => 'texto_en',
        'media_buttons' => false, 
        'textarea_rows' => 10,
        'tinymce'       => true,
        'quicktags'     => true
    ));
    echo '</div>';
}

add_action( 'save_post', 'salvar_dados_projeto_extra' );
function salvar_dados_projeto_extra( $post_id ) {
    if ( ! isset( $_POST['dados_projeto_nonce_campo'] ) || ! wp_verify_nonce( $_POST['dados_projeto_nonce_campo'], 'salvar_dados_projeto_nonce' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    if ( isset( $_POST['autoria_pt'] ) ) update_post_meta( $post_id, 'autoria_pt', sanitize_textarea_field( $_POST['autoria_pt'] ) );
    if ( isset( $_POST['autoria_en'] ) ) update_post_meta( $post_id, 'autoria_en', sanitize_textarea_field( $_POST['autoria_en'] ) );
    
    if ( isset( $_POST['titulo_en'] ) ) update_post_meta( $post_id, 'titulo_en', sanitize_text_field( $_POST['titulo_en'] ) );
    if ( isset( $_POST['texto_en'] ) ) update_post_meta( $post_id, 'texto_en', wp_kses_post( $_POST['texto_en'] ) );
}

add_action('wp_enqueue_scripts', 'tiete_enqueue_scripts');
function tiete_enqueue_scripts() {
    // 1. Google Fonts
    wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap', array(), null);

    // 2. Estilo principal do tema (style.css)
    wp_enqueue_style('tiete-style', get_stylesheet_uri(), array(), '24.1');

    // 3. Script do Lenis (Smooth Scroll)
    wp_enqueue_script('lenis', 'https://unpkg.com/lenis@1.1.13/dist/lenis.min.js', array(), '1.1.13', true);

    // 4. Nosso script principal (main.js) - Carrega no footer (true) e depende do Lenis
    wp_enqueue_script('tiete-main', get_template_directory_uri() . '/js/main.js', array('lenis'), '24.1', true);

    // 5. Segurança: Passando variáveis do PHP para o JS de forma limpa e sanitizada
    wp_localize_script('tiete-main', 'temaConfig', array(
        'filtroAtivo'  => isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '',
        'lang'         => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'pt',
        'homeUrl'      => esc_url( home_url('/') ),
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'albumArquivo' => tiete_get_arquivo_leve(),
    ));
}

// =========================================================================
// 📷 BG-ZOOM IMAGE — SELETOR DE IMAGEM POR POST
// =========================================================================
// Registrar meta field
add_action('init', function() {
    register_post_meta('post', 'bg_zoom_image', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
    ));
});

// Adicionar meta box no editor
add_action('add_meta_boxes', function() {
    add_meta_box(
        'bg_zoom_image_meta',
        '📷 Imagem BG-ZOOM',
        'renderizar_meta_box_bg_zoom',
        'post',
        'normal',
        'high'
    );
});

// Renderizar o meta box
function renderizar_meta_box_bg_zoom($post) {
    wp_nonce_field('salvar_bg_zoom_nonce', 'bg_zoom_nonce_campo');
    
    $bgZoomUrl = get_post_meta($post->ID, 'bg_zoom_image', true);
    
    ?>
    <div style="margin-bottom: 20px;">
        <p><strong>Escolha uma imagem para exibir na barra lateral ao fazer zoom:</strong></p>
        
        <!-- Preview -->
        <div style="margin-bottom: 15px;">
            <img id="bg-zoom-preview" 
                 src="<?php echo $bgZoomUrl ? esc_url($bgZoomUrl) : ''; ?>" 
                 style="max-width: 300px; max-height: 400px; <?php echo $bgZoomUrl ? '' : 'display:none;'; ?>" />
        </div>
        
        <!-- Campo hidden para armazenar URL -->
        <input type="hidden" id="bg_zoom_image_url" name="bg_zoom_image_url" value="<?php echo esc_url($bgZoomUrl); ?>" />
        
        <!-- Botões -->
        <button type="button" class="button button-primary" id="select-bg-zoom" onclick="selecionarBgZoom()">
            Selecionar Imagem
        </button>
        <button type="button" class="button" id="remove-bg-zoom" onclick="removerBgZoom()" 
                <?php echo $bgZoomUrl ? '' : 'style="display:none;"'; ?>>
            Remover Imagem
        </button>
    </div>
    
    <script>
    function selecionarBgZoom() {
        var frame = wp.media({
            title: 'Selecionar Imagem BG-ZOOM',
            button: { text: 'Usar Esta Imagem' },
            multiple: false,
            library: { type: 'image' }
        });
        
        frame.on('select', function() {
            var attachment = frame.state().get('selection').first().toJSON();
            document.getElementById('bg_zoom_image_url').value = attachment.url;
            document.getElementById('bg-zoom-preview').src = attachment.url;
            document.getElementById('bg-zoom-preview').style.display = 'block';
            document.getElementById('remove-bg-zoom').style.display = 'inline-block';
        });
        
        frame.open();
    }
    
    function removerBgZoom() {
        document.getElementById('bg_zoom_image_url').value = '';
        document.getElementById('bg-zoom-preview').style.display = 'none';
        document.getElementById('remove-bg-zoom').style.display = 'none';
    }
    </script>
    <?php
}

// =========================================================================
// 🎵 ALBUM DA SEMANA — CPT, META BOX E AJAX
// =========================================================================

// --- CPT ---
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

// --- HELPER: dados leves para o arquivo (injetados via wp_localize_script) ---
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
            'thumb_url'=> get_the_post_thumbnail_url($id, 'thumbnail')
                         ?: get_post_meta($id, 'spotify_cover_url', true)
                         ?: '',
        ];
    }
    wp_reset_postdata();
    return $lista;
}

// --- META BOX ---
add_action('add_meta_boxes', 'tiete_adicionar_meta_box_album');
function tiete_adicionar_meta_box_album() {
    add_meta_box(
        'album_dados_extra',
        '🎵 Dados do Álbum',
        'tiete_renderizar_meta_box_album',
        'album-semana',
        'normal',
        'high'
    );
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

    <!-- ── BUSCA SPOTIFY ─────────────────────────────── -->
    <div style="background:#f0f6fc; border:1px solid #c3d9f0; border-radius:4px; padding:14px 16px; margin-bottom:18px;">
        <strong style="display:block; margin-bottom:8px;">🔍 Buscar dados no Spotify</strong>
        <div style="display:flex; gap:8px; align-items:center; flex-wrap:wrap;">
            <input type="text" id="spotify_album_input" placeholder="Cole a URL do álbum no Spotify"
                   style="flex:1; min-width:260px; padding:6px 8px;">
            <button type="button" class="button button-primary" onclick="tieteSpotifyBuscar()">Buscar ▸</button>
        </div>
        <p id="spotify_status" style="margin:8px 0 0; font-size:12px; color:#555;"></p>
        <?php wp_nonce_field('spotify_buscar_album', 'spotify_nonce_campo'); ?>
        <input type="hidden" id="spotify_cover_url" name="spotify_cover_url" value="<?php echo esc_url($spotify_cover); ?>">
    </div>

    <!-- Capa atual (thumbnail WP ou Spotify) -->
    <?php if ($cover_preview) : ?>
    <div style="margin-bottom:16px; display:flex; align-items:center; gap:12px;">
        <img id="spotify_cover_preview" src="<?php echo esc_url($cover_preview); ?>"
             style="width:80px; height:80px; border-radius:50%; object-fit:cover;">
        <small style="color:#888;"><?php echo $has_thumbnail ? 'Imagem destacada (WordPress)' : 'Capa do Spotify — será importada ao salvar'; ?></small>
    </div>
    <?php else : ?>
    <img id="spotify_cover_preview" src="" style="width:80px; height:80px; border-radius:50%; object-fit:cover; display:none; margin-bottom:16px;">
    <?php endif; ?>

    <!-- ── CAMPOS ──────────────────────────────────────── -->
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
            <input type="text" id="album_audio_url" name="album_audio_url" value="<?php echo esc_url($audio_url); ?>"
                   style="flex:1; min-width:200px; padding:5px;" placeholder="Preenchido automaticamente pelo Buscar ▸">
            <?php if ($audio_url) : ?>
                <audio controls src="<?php echo esc_url($audio_url); ?>" style="max-width:200px;"></audio>
            <?php endif; ?>
        </div>
    </div>

    <div style="margin-bottom:15px;">
        <label for="album_faixa_destaque" style="display:block; margin-bottom:4px;"><strong>Faixa Recomendada (nome):</strong></label>
        <input type="text" id="album_faixa_destaque" name="album_faixa_destaque" value="<?php echo esc_attr($faixa_destaque); ?>"
               style="width:100%; max-width:400px; padding:5px;" placeholder="ex: Track 3 - Nome da Música">
    </div>

    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <div style="flex:1; min-width:200px;">
            <label for="album_tracklist" style="display:block; margin-bottom:4px;"><strong>Tracklist</strong> <small>(uma faixa por linha)</small>:</label>
            <textarea id="album_tracklist" name="album_tracklist" rows="8" style="width:100%; padding:5px;"><?php echo esc_textarea($tracklist); ?></textarea>
        </div>
        <div style="flex:1; min-width:200px;">
            <label for="album_streaming_links" style="display:block; margin-bottom:4px;"><strong>Links de Streaming</strong> <small>(formato: <code>Plataforma|https://url</code> por linha)</small>:</label>
            <textarea id="album_streaming_links" name="album_streaming_links" rows="8" style="width:100%; padding:5px;"
                      placeholder="Spotify|https://open.spotify.com/...&#10;Apple Music|https://music.apple.com/..."><?php echo esc_textarea($streaming_links); ?></textarea>
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

            // Preencher título do post (editor clássico)
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

    // Capa do Spotify: importar para mídia e definir como imagem destacada (se ainda não tiver)
    $spotify_cover = isset($_POST['spotify_cover_url']) ? esc_url_raw($_POST['spotify_cover_url']) : '';
    update_post_meta($post_id, 'spotify_cover_url', $spotify_cover);

    if ($spotify_cover && !has_post_thumbnail($post_id)) {
        tiete_importar_capa_spotify($spotify_cover, $post_id, get_post_field('post_title', $post_id));
    }
}

// --- AJAX: retorna dados completos de um álbum pelo ID ---
add_action('wp_ajax_get_album_semana',        'tiete_ajax_get_album');
add_action('wp_ajax_nopriv_get_album_semana', 'tiete_ajax_get_album');
function tiete_ajax_get_album() {
    $id   = intval($_GET['id'] ?? 0);
    $post = $id ? get_post($id) : null;
    if (!$post || $post->post_type !== 'album-semana') { wp_send_json_error(); return; }

    $tracklist_raw = get_post_meta($id, 'album_tracklist', true);
    $tracklist     = $tracklist_raw
        ? array_values(array_filter(array_map('trim', explode("\n", $tracklist_raw))))
        : [];

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
        'cover_url'       => get_the_post_thumbnail_url($id, 'large')
                            ?: get_post_meta($id, 'spotify_cover_url', true)
                            ?: '',
        'audio_url'       => get_post_meta($id, 'album_audio_url',      true),
        'review_html'     => apply_filters('the_content', $post->post_content),
        'faixa_destaque'  => get_post_meta($id, 'album_faixa_destaque', true),
        'tracklist'       => $tracklist,
        'streaming_links' => $streaming,
    ]);
}

// =========================================================================
// 🎵 SPOTIFY API
// =========================================================================

// --- Token com cache de 55 minutos (expira em 60) ---
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

// --- AJAX admin: buscar dados do álbum no Spotify ---
add_action('wp_ajax_spotify_buscar_album', 'tiete_ajax_spotify_buscar_album');
function tiete_ajax_spotify_buscar_album() {
    check_ajax_referer('spotify_buscar_album', 'nonce');
    if (!current_user_can('edit_posts')) { wp_send_json_error('Permissão negada.'); return; }

    $input = sanitize_text_field($_POST['album_id'] ?? '');
    if (!$input) { wp_send_json_error('URL ou ID inválido.'); return; }

    // Extrai o ID do álbum da URL, se necessário
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

    // Tracklist
    $tracks    = $album['tracks']['items'] ?? [];
    $tracklist = array_map(fn($t) => $t['name'], $tracks);

    // Preview: Spotify removeu preview_url em 2023 — vai direto para Deezer
    $preview_url    = '';
    $faixa_destaque = '';

    // Artistas
    $artistas = implode(', ', array_map(fn($a) => $a['name'], $album['artists'] ?? []));

    // Gêneros: tenta no álbum; se vazio, busca no artista principal
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

    // Capa (resolução máxima)
    $cover_url = $album['images'][0]['url'] ?? '';

    // Faixa favorita indicada pelo usuário (usada para selecionar o preview)
    $faixa_hint = sanitize_text_field($_POST['faixa_hint'] ?? '');

    // 1º fallback: Deezer (API pública sem auth)
    $itunes_debug = '';
    if (!$preview_url) {
        [$preview_url, $faixa_destaque, $itunes_debug] = tiete_get_preview_deezer($artistas, $album['name'], $faixa_hint);
    }

    // 2º fallback: iTunes
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

// Normaliza string para comparação fuzzy de nomes de faixas
function tiete_normalizar_faixa($s) {
    return preg_replace('/[^a-z0-9]/', '', strtolower($s));
}

// --- Preview via Deezer API (gratuito, sem autenticação) ---
// $faixa_hint: se preenchido, busca aquela faixa específica; senão, pega a mais popular
// Retorna [$preview_url, $track_name, $debug_msg]
function tiete_get_preview_deezer($artista, $album_nome, $faixa_hint = '') {
    $args = [
        'timeout'   => 15,
        'sslverify' => false,
        'headers'   => ['User-Agent' => 'Mozilla/5.0 (compatible; WordPress/' . get_bloginfo('version') . ')'],
    ];

    $query = urlencode(trim($artista . ' ' . $album_nome));
    $resp  = wp_remote_get("https://api.deezer.com/search/album?q={$query}&limit=5", $args);

    if (is_wp_error($resp)) return ['', '', 'Deezer erro de conexão: ' . $resp->get_error_message()];

    $albums = json_decode(wp_remote_retrieve_body($resp), true)['data'] ?? [];
    if (!$albums) return ['', '', 'Deezer: álbum não encontrado'];

    $album_id = $albums[0]['id'];
    $resp2    = wp_remote_get("https://api.deezer.com/album/{$album_id}/tracks?limit=50", $args);

    if (is_wp_error($resp2)) return ['', '', 'Deezer tracks erro: ' . $resp2->get_error_message()];

    $tracks = json_decode(wp_remote_retrieve_body($resp2), true)['data'] ?? [];
    if (!$tracks) return ['', '', "Deezer: nenhuma faixa encontrada (álbum ID {$album_id})"];

    // Se uma faixa foi especificada, tenta encontrá-la pelo nome (fuzzy)
    if ($faixa_hint) {
        $hint_norm = tiete_normalizar_faixa($faixa_hint);
        foreach ($tracks as $t) {
            $titulo_norm = tiete_normalizar_faixa($t['title'] ?? '');
            $match = $titulo_norm && $hint_norm &&
                     (str_contains($titulo_norm, $hint_norm) || str_contains($hint_norm, $titulo_norm));
            if ($match && !empty($t['preview'])) {
                return [$t['preview'], $t['title'], ''];
            }
        }
        // Faixa especificada não encontrada — continua para a mais popular
    }

    // Faixa mais popular com preview
    usort($tracks, fn($a, $b) => ($b['rank'] ?? 0) - ($a['rank'] ?? 0));
    foreach ($tracks as $t) {
        if (!empty($t['preview'])) {
            return [$t['preview'], $t['title'] ?? '', ''];
        }
    }

    return ['', '', 'Deezer: ' . count($tracks) . ' faixas sem preview'];
}

// --- Fallback de preview via iTunes Search API (gratuito, sem autenticação) ---
// $faixa_hint: se preenchido, busca aquela faixa específica; senão, pega a primeira com preview
// Retorna [$preview_url, $track_name, $debug_msg]
function tiete_get_preview_itunes($artista, $album_nome, $faixa_hint = '') {
    $args = [
        'timeout'   => 15,
        'sslverify' => false,
        'headers'   => ['User-Agent' => 'Mozilla/5.0 (compatible; WordPress/' . get_bloginfo('version') . ')'],
    ];

    $query = urlencode(trim($artista . ' ' . $album_nome));

    // Tenta loja BR primeiro (cobre música brasileira), depois loja US
    foreach (['br', 'us'] as $country) {
        $url  = "https://itunes.apple.com/search?term={$query}&entity=song&country={$country}&limit=25";
        $resp = wp_remote_get($url, $args);

        if (is_wp_error($resp)) {
            return ['', '', "iTunes erro de conexão ({$country}): " . $resp->get_error_message()];
        }

        $tracks = json_decode(wp_remote_retrieve_body($resp), true)['results'] ?? [];

        // Se uma faixa foi especificada, tenta encontrá-la primeiro (fuzzy)
        if ($faixa_hint) {
            $hint_norm = tiete_normalizar_faixa($faixa_hint);
            foreach ($tracks as $t) {
                if (($t['wrapperType'] ?? '') !== 'track' || empty($t['previewUrl'])) continue;
                $track_norm = tiete_normalizar_faixa($t['trackName'] ?? '');
                if ($track_norm && $hint_norm &&
                    (str_contains($track_norm, $hint_norm) || str_contains($hint_norm, $track_norm))) {
                    return [$t['previewUrl'], $t['trackName'] ?? '', ''];
                }
            }
        }

        // Primeira faixa com preview (sem hint, ou hint não encontrado)
        foreach ($tracks as $t) {
            if (!empty($t['previewUrl'])) {
                return [$t['previewUrl'], $t['trackName'] ?? '', ''];
            }
        }

        $debug_country = "iTunes {$country}: " . count($tracks) . " faixas, nenhuma com preview";
    }

    return ['', '', $debug_country ?? 'iTunes: sem resultados'];
}

// --- Importa capa do Spotify para a biblioteca de mídia do WP ---
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

// Salvar meta data ao guardar post
add_action('save_post_post', function($post_id) {
    if (!isset($_POST['bg_zoom_nonce_campo']) || !wp_verify_nonce($_POST['bg_zoom_nonce_campo'], 'salvar_bg_zoom_nonce')) {
        return;
    }
    
    if (isset($_POST['bg_zoom_image_url'])) {
        update_post_meta($post_id, 'bg_zoom_image', esc_url_raw($_POST['bg_zoom_image_url']));
    }
});