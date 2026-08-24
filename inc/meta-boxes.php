<?php
// =========================================================================
// TRADUÇÃO: CAIXA DE CONTEÚDO EM INGLÊS (CUSTOM META BOX)
// =========================================================================

add_action( 'add_meta_boxes', 'adicionar_caixa_dados_projeto' );
function adicionar_caixa_dados_projeto() {
    add_meta_box( 
        'caixa_dados_projeto', 
        'Dados Adicionais e Traducao (EN/JP)', 
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
    $autoria_ja = get_post_meta( $post->ID, 'autoria_ja', true );
    $titulo_en = get_post_meta( $post->ID, 'titulo_en', true );
    $titulo_ja = get_post_meta( $post->ID, 'titulo_ja', true );
    $texto_en = get_post_meta( $post->ID, 'texto_en', true );
    $texto_ja = get_post_meta( $post->ID, 'texto_ja', true );

    echo '<div style="display:flex; gap: 20px; margin-bottom: 20px;">';
        echo '<div style="flex: 1;">';
        echo '<label for="autoria_pt" style="display:block; margin-bottom:5px;"><strong>Autoria / Ano (PT):</strong><br><small>Use Enter para quebrar linha</small></label>';
        echo '<textarea id="autoria_pt" name="autoria_pt" rows="3" style="width:100%; padding:5px;">' . esc_textarea( $autoria_pt ) . '</textarea>';
        echo '</div>';

        echo '<div style="flex: 1;">';
        echo '<label for="autoria_en" style="display:block; margin-bottom:5px;"><strong>Autoria / Ano (EN):</strong><br><small>Use Enter para quebrar linha</small></label>';
        echo '<textarea id="autoria_en" name="autoria_en" rows="3" style="width:100%; padding:5px;">' . esc_textarea( $autoria_en ) . '</textarea>';
        echo '</div>';

        echo '<div style="flex: 1;">';
        echo '<label for="autoria_ja" style="display:block; margin-bottom:5px;"><strong>Autoria / Ano (JP):</strong><br><small>Use Enter para quebrar linha</small></label>';
        echo '<textarea id="autoria_ja" name="autoria_ja" rows="3" style="width:100%; padding:5px;">' . esc_textarea( $autoria_ja ) . '</textarea>';
        echo '</div>';
    echo '</div>';

    echo '<hr style="margin: 20px 0;">';

    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="titulo_en" style="display:block; margin-bottom:5px;"><strong>Título do Projeto em Inglês:</strong></label>';
    echo '<input type="text" id="titulo_en" name="titulo_en" value="' . esc_attr( $titulo_en ) . '" style="width:100%; max-width:400px; padding:5px;">';
    echo '</div>';

    echo '<div style="margin-bottom: 15px;">';
    echo '<label for="titulo_ja" style="display:block; margin-bottom:5px;"><strong>Titulo do Projeto em Japones:</strong></label>';
    echo '<input type="text" id="titulo_ja" name="titulo_ja" value="' . esc_attr( $titulo_ja ) . '" style="width:100%; max-width:400px; padding:5px;">';
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

    echo '<div style="margin-top: 25px;">';
    echo '<label style="display:block; margin-bottom:10px;"><strong>Texto em Japones (Ficha Tecnica / Descricao):</strong></label>';
    wp_editor( $texto_ja, 'texto_ja', array(
        'textarea_name' => 'texto_ja',
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
    if ( isset( $_POST['autoria_ja'] ) ) update_post_meta( $post_id, 'autoria_ja', sanitize_textarea_field( $_POST['autoria_ja'] ) );
    
    if ( isset( $_POST['titulo_en'] ) ) update_post_meta( $post_id, 'titulo_en', sanitize_text_field( $_POST['titulo_en'] ) );
    if ( isset( $_POST['titulo_ja'] ) ) update_post_meta( $post_id, 'titulo_ja', sanitize_text_field( $_POST['titulo_ja'] ) );
    if ( isset( $_POST['texto_en'] ) ) update_post_meta( $post_id, 'texto_en', wp_kses_post( $_POST['texto_en'] ) );
    if ( isset( $_POST['texto_ja'] ) ) update_post_meta( $post_id, 'texto_ja', wp_kses_post( $_POST['texto_ja'] ) );
}

// =========================================================================
// 📷 BG-ZOOM IMAGE — SELETOR DE IMAGEM POR POST
// =========================================================================
add_action('init', function() {
    register_post_meta('post', 'bg_zoom_image', array(
        'type'          => 'string',
        'single'        => true,
        'show_in_rest'  => true,
    ));
});

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

function renderizar_meta_box_bg_zoom($post) {
    wp_nonce_field('salvar_bg_zoom_nonce', 'bg_zoom_nonce_campo');
    $bgZoomUrl = get_post_meta($post->ID, 'bg_zoom_image', true);
    ?>
    <div style="margin-bottom: 20px;">
        <p><strong>Escolha uma imagem para exibir na barra lateral ao fazer zoom:</strong></p>
        <div style="margin-bottom: 15px;">
            <img id="bg-zoom-preview" src="<?php echo $bgZoomUrl ? esc_url($bgZoomUrl) : ''; ?>" style="max-width: 300px; max-height: 400px; <?php echo $bgZoomUrl ? '' : 'display:none;'; ?>" />
        </div>
        <input type="hidden" id="bg_zoom_image_url" name="bg_zoom_image_url" value="<?php echo esc_url($bgZoomUrl); ?>" />
        <button type="button" class="button button-primary" id="select-bg-zoom" onclick="selecionarBgZoom()">Selecionar Imagem</button>
        <button type="button" class="button" id="remove-bg-zoom" onclick="removerBgZoom()" <?php echo $bgZoomUrl ? '' : 'style="display:none;"'; ?>>Remover Imagem</button>
    </div>
    <script>
    function selecionarBgZoom() {
        var frame = wp.media({ title: 'Selecionar Imagem BG-ZOOM', button: { text: 'Usar Esta Imagem' }, multiple: false, library: { type: 'image' } });
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

add_action('save_post_post', function($post_id) {
    if (!isset($_POST['bg_zoom_nonce_campo']) || !wp_verify_nonce($_POST['bg_zoom_nonce_campo'], 'salvar_bg_zoom_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;
    if (isset($_POST['bg_zoom_image_url'])) {
        update_post_meta($post_id, 'bg_zoom_image', esc_url_raw($_POST['bg_zoom_image_url']));
    }
});

// =========================================================================
// 🔴 GRÁFICO YAYOI — COORDENADAS DO PROJETO
// =========================================================================

add_action('add_meta_boxes', 'tiete_adicionar_meta_box_yayoi');
function tiete_adicionar_meta_box_yayoi() {
    add_meta_box(
        'yayoi_dados_projeto',
        'Gráfico Jomon-Yayoi',
        'tiete_renderizar_meta_box_yayoi',
        'post', // Aparece apenas nos Projetos
        'side', // 'side' coloca a caixa na barra lateral direita
        'default'
    );
}

function tiete_renderizar_meta_box_yayoi($post) {
    wp_nonce_field('salvar_yayoi_nonce', 'yayoi_nonce_campo');

    $eixo_x = get_post_meta($post->ID, 'yayoi_eixo_x', true);
    $eixo_y = get_post_meta($post->ID, 'yayoi_eixo_y', true);
    ?>
    
    <p style="font-size: 12px; color: #666; margin-top: 0;">
        Defina a posição deste projeto no gráfico.<br>
        <em>Deixe em branco se não quiser que ele apareça no gráfico Yayoi.</em>
    </p>

    <div style="margin-bottom: 15px;">
        <label for="yayoi_eixo_x" style="display:block; margin-bottom:5px;">
            <strong>↔ Eixo Horizontal (Estilo):</strong><br>
            <small>0 = Bruto (Vermelho) | 100 = Limpo (Branco)</small>
        </label>
        <input type="number" id="yayoi_eixo_x" name="yayoi_eixo_x" value="<?php echo esc_attr($eixo_x); ?>" min="0" max="100" style="width:100%; padding: 4px;">
    </div>

    <div style="margin-bottom: 5px;">
        <label for="yayoi_eixo_y" style="display:block; margin-bottom:5px;">
            <strong>↕ Eixo Vertical (Acesso):</strong><br>
            <small>0 = Privado | 100 = Universal</small>
        </label>
        <input type="number" id="yayoi_eixo_y" name="yayoi_eixo_y" value="<?php echo esc_attr($eixo_y); ?>" min="0" max="100" style="width:100%; padding: 4px;">
    </div>
    
    <?php
}

add_action('save_post', 'tiete_salvar_dados_yayoi');
function tiete_salvar_dados_yayoi($post_id) {
    // Verificações de segurança padrão
    if (!isset($_POST['yayoi_nonce_campo']) || !wp_verify_nonce($_POST['yayoi_nonce_campo'], 'salvar_yayoi_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    // Salvar Eixo X
    if (isset($_POST['yayoi_eixo_x']) && $_POST['yayoi_eixo_x'] !== '') {
        update_post_meta($post_id, 'yayoi_eixo_x', intval($_POST['yayoi_eixo_x']));
    } else {
        delete_post_meta($post_id, 'yayoi_eixo_x'); // Remove se o utilizador apagar o número
    }

    // Salvar Eixo Y
    if (isset($_POST['yayoi_eixo_y']) && $_POST['yayoi_eixo_y'] !== '') {
        update_post_meta($post_id, 'yayoi_eixo_y', intval($_POST['yayoi_eixo_y']));
    } else {
        delete_post_meta($post_id, 'yayoi_eixo_y');
    }
}

// ========================================================================= //
// 📄 META BOX: TRADUÇÃO DE PÁGINAS (SOBRE)
// ========================================================================= //
add_action('add_meta_boxes', 'tiete_adicionar_meta_box_paginas');
function tiete_adicionar_meta_box_paginas() {
    add_meta_box(
        'pagina_traducao_idiomas',
        'Traducao do Conteudo (EN/JP)',
        'tiete_renderizar_meta_box_paginas',
        'page', // Aparece apenas em Páginas
        'normal',
        'high'
    );
}

function tiete_renderizar_meta_box_paginas($post) {
    wp_nonce_field('salvar_pagina_nonce', 'pagina_nonce_campo');
    $texto_en = get_post_meta($post->ID, 'conteudo_en', true);
    $texto_ja = get_post_meta($post->ID, 'conteudo_ja', true);
    
    echo '<p>Escreva aqui as versoes traduzidas do texto principal desta pagina.</p>';
    
    echo '<h3>Ingles</h3>';
    wp_editor($texto_en, 'conteudo_en', [
        'textarea_rows' => 12,
        'media_buttons' => false,
        'teeny'         => true
    ]);

    echo '<h3 style="margin-top: 25px;">Japones</h3>';
    wp_editor($texto_ja, 'conteudo_ja', [
        'textarea_rows' => 12,
        'media_buttons' => false,
        'teeny'         => true
    ]);
}

add_action('save_post_page', 'tiete_salvar_pagina_traducao');
function tiete_salvar_pagina_traducao($post_id) {
    if (!isset($_POST['pagina_nonce_campo']) || !wp_verify_nonce($_POST['pagina_nonce_campo'], 'salvar_pagina_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_page', $post_id)) return;

    if (isset($_POST['conteudo_en'])) {
        update_post_meta($post_id, 'conteudo_en', wp_kses_post($_POST['conteudo_en']));
    }

    if (isset($_POST['conteudo_ja'])) {
        update_post_meta($post_id, 'conteudo_ja', wp_kses_post($_POST['conteudo_ja']));
    }
}

// ========================================================================= //
// 🎓 DADOS DO CURSO — PROFESSOR, DATA, PREÇO E INSCRIÇÃO
// ========================================================================= //
// A ementa/descrição do curso reaproveita os campos de Texto PT/EN/JP
// da caixa "Dados Adicionais e Traducao" acima — aqui ficam só os
// dados comerciais específicos de curso.
add_action('add_meta_boxes', 'tiete_adicionar_meta_box_curso');
function tiete_adicionar_meta_box_curso() {
    add_meta_box(
        'curso_dados_extra',
        '🎓 Dados do Curso',
        'tiete_renderizar_meta_box_curso',
        'post',
        'normal',
        'high'
    );
}

function tiete_renderizar_meta_box_curso($post) {
    wp_nonce_field('salvar_curso_nonce', 'curso_nonce_campo');

    $professor       = get_post_meta($post->ID, 'curso_professor', true);
    $data_hora       = get_post_meta($post->ID, 'curso_data_hora', true);
    $duracao         = get_post_meta($post->ID, 'curso_duracao',   true);
    $preco           = get_post_meta($post->ID, 'curso_preco',     true);
    $vagas           = get_post_meta($post->ID, 'curso_vagas',     true);
    $cta_url         = get_post_meta($post->ID, 'curso_cta_url',   true);
    $shopify_produto = get_post_meta($post->ID, 'curso_shopify_produto', true);
    ?>
    <p style="font-size:12px; color:#666; margin-top:0;">
        Preencha estes campos para posts da categoria <strong>Cursos</strong> — eles ativam o layout de venda na barra lateral. A ementa continua nos campos de Texto PT/EN/JP da caixa de tradução acima.
    </p>

    <div style="background:#f0f6fc; border:1px solid #c3d9f0; border-radius:4px; padding:14px 16px; margin-bottom:18px;">
        <label for="curso_shopify_produto" style="display:block; margin-bottom:4px;"><strong>🛒 Handle do produto no Shopify</strong></label>
        <input type="text" id="curso_shopify_produto" name="curso_shopify_produto" value="<?php echo esc_attr($shopify_produto); ?>" placeholder="ex: kazuo-shinohara" style="width:100%; max-width:400px; padding:5px;">
        <p style="font-size:12px; color:#555; margin:6px 0 0;">É a parte final da URL do produto na sua loja Shopify (<code>.../products/<strong>kazuo-shinohara</strong></code>). Preenchido isso, o botão "Inscrever-se" compra de verdade via Shopify — o link abaixo vira só um plano B enquanto isso não estiver configurado.</p>
    </div>

    <div style="display:flex; gap:20px; flex-wrap:wrap; margin-bottom:15px;">
        <div style="flex:1; min-width:200px;">
            <label for="curso_professor" style="display:block; margin-bottom:4px;"><strong>Professor:</strong></label>
            <input type="text" id="curso_professor" name="curso_professor" value="<?php echo esc_attr($professor); ?>" style="width:100%; padding:5px;">
        </div>
        <div style="flex:0 0 220px;">
            <label for="curso_data_hora" style="display:block; margin-bottom:4px;"><strong>Data e hora da aula ao vivo:</strong></label>
            <input type="datetime-local" id="curso_data_hora" name="curso_data_hora" value="<?php echo esc_attr($data_hora); ?>" style="width:100%; padding:5px;">
        </div>
        <div style="flex:0 0 160px;">
            <label for="curso_duracao" style="display:block; margin-bottom:4px;"><strong>Duração:</strong></label>
            <input type="text" id="curso_duracao" name="curso_duracao" value="<?php echo esc_attr($duracao); ?>" placeholder="ex: 2h" style="width:100%; padding:5px;">
        </div>
    </div>
    <div style="display:flex; gap:20px; flex-wrap:wrap;">
        <div style="flex:0 0 160px;">
            <label for="curso_preco" style="display:block; margin-bottom:4px;"><strong>Preço:</strong></label>
            <input type="text" id="curso_preco" name="curso_preco" value="<?php echo esc_attr($preco); ?>" placeholder="ex: R$ 350" style="width:100%; padding:5px;">
        </div>
        <div style="flex:0 0 120px;">
            <label for="curso_vagas" style="display:block; margin-bottom:4px;"><strong>Vagas:</strong></label>
            <input type="number" id="curso_vagas" name="curso_vagas" value="<?php echo esc_attr($vagas); ?>" min="0" style="width:100%; padding:5px;">
        </div>
        <div style="flex:1; min-width:220px;">
            <label for="curso_cta_url" style="display:block; margin-bottom:4px;"><strong>Link alternativo do botão "Inscrever-se"</strong> <small>(usado só se o handle do Shopify acima estiver vazio)</small>:</label>
            <input type="text" id="curso_cta_url" name="curso_cta_url" value="<?php echo esc_attr($cta_url); ?>" placeholder="https://..." style="width:100%; padding:5px;">
        </div>
    </div>
    <?php
}

add_action('save_post', 'tiete_salvar_dados_curso');
function tiete_salvar_dados_curso($post_id) {
    if (!isset($_POST['curso_nonce_campo']) || !wp_verify_nonce($_POST['curso_nonce_campo'], 'salvar_curso_nonce')) return;
    if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
    if (!current_user_can('edit_post', $post_id)) return;

    if (isset($_POST['curso_professor'])) update_post_meta($post_id, 'curso_professor', sanitize_text_field($_POST['curso_professor']));
    if (isset($_POST['curso_data_hora'])) update_post_meta($post_id, 'curso_data_hora', sanitize_text_field($_POST['curso_data_hora']));
    if (isset($_POST['curso_duracao']))   update_post_meta($post_id, 'curso_duracao',   sanitize_text_field($_POST['curso_duracao']));
    if (isset($_POST['curso_preco']))     update_post_meta($post_id, 'curso_preco',     sanitize_text_field($_POST['curso_preco']));
    if (isset($_POST['curso_cta_url']))   update_post_meta($post_id, 'curso_cta_url',   esc_url_raw($_POST['curso_cta_url']));
    if (isset($_POST['curso_shopify_produto'])) update_post_meta($post_id, 'curso_shopify_produto', sanitize_title($_POST['curso_shopify_produto']));

    if (isset($_POST['curso_vagas']) && $_POST['curso_vagas'] !== '') {
        update_post_meta($post_id, 'curso_vagas', intval($_POST['curso_vagas']));
    } else {
        delete_post_meta($post_id, 'curso_vagas');
    }
}
