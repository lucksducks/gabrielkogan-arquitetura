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
        'filtroAtivo' => isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '',
        'lang'        => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'pt',
        'homeUrl'     => esc_url( home_url('/') ), // útil para o JS construir links
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

// Salvar meta data ao guardar post
add_action('save_post_post', function($post_id) {
    if (!isset($_POST['bg_zoom_nonce_campo']) || !wp_verify_nonce($_POST['bg_zoom_nonce_campo'], 'salvar_bg_zoom_nonce')) {
        return;
    }
    
    if (isset($_POST['bg_zoom_image_url'])) {
        update_post_meta($post_id, 'bg_zoom_image', esc_url_raw($_POST['bg_zoom_image_url']));
    }
});