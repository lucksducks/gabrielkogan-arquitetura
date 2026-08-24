<?php
// =========================================================================
// 🛒 PRODUTO — CPT UNIFICADO PARA A LOJA (curso | livro | objeto)
// =========================================================================
// Substitui o antigo esquema "post na categoria cursos". Cada produto tem
// um `tipo` (taxonomia) que decide quais campos e qual ficha aparecem.
// O elo com o Shopify é o meta `shopify_handle` (comum a todos os tipos).
// A ementa/descrição e traduções reaproveitam os campos texto_* / titulo_*.
// =========================================================================

if ( ! defined( 'ABSPATH' ) ) exit;

// -------------------------------------------------------------------------
// 1. CPT + TAXONOMIA
// -------------------------------------------------------------------------
add_action( 'init', 'tiete_register_produto_cpt' );
function tiete_register_produto_cpt() {

    register_post_type( 'produto', array(
        'labels' => array(
            'name'          => 'Loja',
            'singular_name' => 'Produto',
            'add_new_item'  => 'Adicionar Produto',
            'edit_item'     => 'Editar Produto',
            'all_items'     => 'Todos os Produtos',
            'menu_name'     => 'Loja',
        ),
        'public'       => true,
        'show_ui'      => true,
        'show_in_menu' => true,
        'show_in_rest' => true, // habilita editor de blocos + REST
        'has_archive'  => true,
        'menu_icon'    => 'dashicons-cart',
        'rewrite'      => array( 'slug' => 'loja' ),
        'supports'     => array( 'title', 'editor', 'thumbnail' ),
    ) );

    register_taxonomy( 'tipo_produto', 'produto', array(
        'labels' => array(
            'name'          => 'Tipos',
            'singular_name' => 'Tipo',
            'menu_name'     => 'Tipos',
        ),
        'public'            => true,
        'hierarchical'      => true, // aparece como checkbox, igual categorias
        'show_admin_column' => true,
        'show_in_rest'      => true,
        'rewrite'           => array( 'slug' => 'tipo' ),
    ) );
}

// Garante que os três tipos base existam (roda uma vez, barato).
add_action( 'init', 'tiete_seed_tipos_produto', 20 );
function tiete_seed_tipos_produto() {
    if ( get_option( 'tiete_tipos_produto_seed' ) ) return;
    foreach ( array( 'curso' => 'Curso', 'livro' => 'Livro', 'objeto' => 'Objeto' ) as $slug => $nome ) {
        if ( ! term_exists( $slug, 'tipo_produto' ) ) {
            wp_insert_term( $nome, 'tipo_produto', array( 'slug' => $slug ) );
        }
    }
    update_option( 'tiete_tipos_produto_seed', 1 );
}

// -------------------------------------------------------------------------
// 1b. FLUSH DE REWRITE BLINDADO
// -------------------------------------------------------------------------
// As regras de permalink do CPT (/loja/ e /loja/<produto>) precisam estar
// gravadas no banco, senão os singles de produto dão 404 (e a ficha lateral
// nunca abre no front). O flush antigo era "uma vez só" e ficava preso.
// Aqui, sempre que a versão do rewrite mudar OU as regras do /loja/ sumirem,
// regeneramos — barato (roda no máximo uma vez por versão/estado quebrado).
// Para forçar um novo flush no futuro (ex.: troca de slug), basta incrementar
// a constante abaixo.
// -------------------------------------------------------------------------
define( 'TIETE_REWRITE_VERSAO', '2' );

add_action( 'init', 'tiete_garantir_rewrite_produto', 99 );
function tiete_garantir_rewrite_produto() {
    $versao_ok = get_option( 'tiete_rewrite_versao' ) === TIETE_REWRITE_VERSAO;

    // Confere se a regra do CPT realmente existe entre as regras gravadas.
    $regras     = get_option( 'rewrite_rules' );
    $tem_regra  = false;
    if ( is_array( $regras ) ) {
        foreach ( array_keys( $regras ) as $chave ) {
            if ( strpos( $chave, 'loja/' ) === 0 ) { $tem_regra = true; break; }
        }
    }

    if ( $versao_ok && $tem_regra ) return; // tudo certo, nada a fazer

    flush_rewrite_rules();
    update_option( 'tiete_rewrite_versao', TIETE_REWRITE_VERSAO );
}

/**
 * Descobre o tipo (slug) de um produto. Retorna 'curso' | 'livro' | 'objeto' | ''.
 * Usa o primeiro termo atribuído.
 */
function tiete_produto_tipo( $post_id ) {
    $termos = get_the_terms( $post_id, 'tipo_produto' );
    if ( is_wp_error( $termos ) || empty( $termos ) ) return '';
    return $termos[0]->slug;
}

/**
 * Definição dos campos de cada tipo. Fonte única da verdade para o
 * meta box, o save e a renderização da ficha no front-end.
 * Cada campo: chave meta => [rótulo, tipo-html].
 */
function tiete_produto_campos_por_tipo() {
    return array(
        'curso' => array(
            'curso_professor' => array( 'Professor',                'text' ),
            'curso_data_hora' => array( 'Data e hora da aula ao vivo','datetime-local' ),
            'curso_duracao'   => array( 'Duração (ex: 2h)',          'text' ),
            'curso_vagas'     => array( 'Vagas',                     'number' ),
        ),
        'livro' => array(
            'livro_autor'     => array( 'Autor',                     'text' ),
            'livro_editora'   => array( 'Editora',                   'text' ),
            'livro_ano'       => array( 'Ano',                       'text' ),
            'livro_paginas'   => array( 'Páginas',                   'number' ),
            'livro_idioma'    => array( 'Idioma',                    'text' ),
            'livro_formato'   => array( 'Formato (ex: 21×28 cm)',    'text' ),
            'livro_acabamento'=> array( 'Acabamento',                'text' ),
            'livro_isbn'      => array( 'ISBN',                      'text' ),
        ),
        'objeto' => array(
            'objeto_material'  => array( 'Material',                 'text' ),
            'objeto_dimensoes' => array( 'Dimensões',               'text' ),
            'objeto_peso'      => array( 'Peso',                     'text' ),
            'objeto_edicao'    => array( 'Edição / Tiragem',        'text' ),
        ),
    );
}

// -------------------------------------------------------------------------
// 2. META BOXES
// -------------------------------------------------------------------------
add_action( 'add_meta_boxes', 'tiete_produto_meta_boxes' );
function tiete_produto_meta_boxes() {
    add_meta_box( 'produto_shopify', '🛒 Venda (Shopify)',    'tiete_render_mb_shopify',  'produto', 'normal', 'high' );
    add_meta_box( 'produto_dados',   '🧾 Dados do Produto',    'tiete_render_mb_dados',    'produto', 'normal', 'high' );
    add_meta_box( 'produto_trad',    '🌐 Tradução (EN/JP)',    'tiete_render_mb_traducao', 'produto', 'normal', 'default' );
}

function tiete_render_mb_shopify( $post ) {
    wp_nonce_field( 'salvar_produto', 'produto_nonce' );
    $handle   = get_post_meta( $post->ID, 'shopify_handle', true );
    $cta_url  = get_post_meta( $post->ID, 'produto_cta_url', true );
    $pronto   = defined( 'SHOPIFY_STORE_DOMAIN' ) && SHOPIFY_STORE_DOMAIN && defined( 'SHOPIFY_STOREFRONT_TOKEN' ) && SHOPIFY_STOREFRONT_TOKEN;
    ?>
    <div style="background:#f0f6fc;border:1px solid #c3d9f0;border-radius:4px;padding:14px 16px;margin-bottom:14px;">
        <label for="shopify_handle" style="display:block;margin-bottom:4px;"><strong>Handle do produto no Shopify</strong></label>
        <input type="text" id="shopify_handle" name="shopify_handle" value="<?php echo esc_attr( $handle ); ?>" placeholder="ex: tokyo-um-guia" style="width:100%;max-width:400px;padding:5px;">
        <p style="font-size:12px;color:#555;margin:6px 0 0;">A parte final da URL do produto na loja (<code>.../products/<strong>tokyo-um-guia</strong></code>). Com isto preenchido, preço/estoque vêm ao vivo do Shopify e o botão compra de verdade.
        <?php if ( ! $pronto ) : ?><br><strong style="color:#b36b00;">⚠ Faltam as constantes SHOPIFY_STORE_DOMAIN / SHOPIFY_STOREFRONT_TOKEN no wp-config.php.</strong><?php endif; ?></p>
    </div>
    <div>
        <label for="produto_cta_url" style="display:block;margin-bottom:4px;"><strong>Link alternativo do botão</strong> <small>(usado só se o handle acima estiver vazio)</small>:</label>
        <input type="url" id="produto_cta_url" name="produto_cta_url" value="<?php echo esc_attr( $cta_url ); ?>" placeholder="https://..." style="width:100%;max-width:400px;padding:5px;">
    </div>
    <?php
}

function tiete_render_mb_dados( $post ) {
    $tipo_atual = tiete_produto_tipo( $post->ID );
    $campos     = tiete_produto_campos_por_tipo();
    ?>
    <p style="font-size:12px;color:#666;margin-top:0;">
        Selecione o <strong>Tipo</strong> na caixa lateral e <strong>salve</strong> — os campos específicos do tipo aparecem abaixo. Preço não é digitado aqui: vem do Shopify. A descrição/ementa fica no editor principal (e nas traduções).
    </p>
    <?php if ( ! $tipo_atual ) : ?>
        <p style="padding:12px;background:#fcf3f2;border:1px solid #e6c9c6;border-radius:4px;">
            Nenhum <strong>Tipo</strong> definido ainda. Escolha um (Curso / Livro / Objeto) na caixa <em>Tipos</em> à direita e salve.
        </p>
    <?php else : ?>
        <div style="display:flex;gap:18px;flex-wrap:wrap;">
        <?php foreach ( $campos[ $tipo_atual ] as $meta_key => $def ) :
            list( $rotulo, $html_tipo ) = $def;
            $valor = get_post_meta( $post->ID, $meta_key, true );
            ?>
            <div style="flex:1;min-width:190px;">
                <label for="<?php echo esc_attr( $meta_key ); ?>" style="display:block;margin-bottom:4px;"><strong><?php echo esc_html( $rotulo ); ?>:</strong></label>
                <input type="<?php echo esc_attr( $html_tipo ); ?>" id="<?php echo esc_attr( $meta_key ); ?>" name="<?php echo esc_attr( $meta_key ); ?>" value="<?php echo esc_attr( $valor ); ?>" style="width:100%;padding:5px;">
            </div>
        <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php
}

function tiete_render_mb_traducao( $post ) {
    $titulo_en = get_post_meta( $post->ID, 'titulo_en', true );
    $titulo_ja = get_post_meta( $post->ID, 'titulo_ja', true );
    $texto_en  = get_post_meta( $post->ID, 'texto_en', true );
    $texto_ja  = get_post_meta( $post->ID, 'texto_ja', true );
    ?>
    <div style="display:flex;gap:20px;margin-bottom:15px;flex-wrap:wrap;">
        <div style="flex:1;min-width:220px;">
            <label style="display:block;margin-bottom:5px;"><strong>Título (EN):</strong></label>
            <input type="text" name="titulo_en" value="<?php echo esc_attr( $titulo_en ); ?>" style="width:100%;padding:5px;">
        </div>
        <div style="flex:1;min-width:220px;">
            <label style="display:block;margin-bottom:5px;"><strong>Título (JP):</strong></label>
            <input type="text" name="titulo_ja" value="<?php echo esc_attr( $titulo_ja ); ?>" style="width:100%;padding:5px;">
        </div>
    </div>
    <label style="display:block;margin-bottom:6px;"><strong>Descrição (EN):</strong></label>
    <?php wp_editor( $texto_en, 'texto_en', array( 'textarea_name' => 'texto_en', 'media_buttons' => false, 'textarea_rows' => 8, 'teeny' => true ) ); ?>
    <label style="display:block;margin:18px 0 6px;"><strong>Descrição (JP):</strong></label>
    <?php wp_editor( $texto_ja, 'texto_ja', array( 'textarea_name' => 'texto_ja', 'media_buttons' => false, 'textarea_rows' => 8, 'teeny' => true ) ); ?>
    <?php
}

// -------------------------------------------------------------------------
// 3. SAVE
// -------------------------------------------------------------------------
add_action( 'save_post_produto', 'tiete_salvar_produto' );
function tiete_salvar_produto( $post_id ) {
    if ( ! isset( $_POST['produto_nonce'] ) || ! wp_verify_nonce( $_POST['produto_nonce'], 'salvar_produto' ) ) return;
    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) return;
    if ( ! current_user_can( 'edit_post', $post_id ) ) return;

    // Shopify + CTA
    if ( isset( $_POST['shopify_handle'] ) )  update_post_meta( $post_id, 'shopify_handle',  sanitize_title( $_POST['shopify_handle'] ) );
    if ( isset( $_POST['produto_cta_url'] ) )  update_post_meta( $post_id, 'produto_cta_url', esc_url_raw( $_POST['produto_cta_url'] ) );

    // Campos de todos os tipos (salva os que vierem no POST — o tipo atual)
    foreach ( tiete_produto_campos_por_tipo() as $campos ) {
        foreach ( $campos as $meta_key => $def ) {
            if ( ! isset( $_POST[ $meta_key ] ) ) continue;
            $raw = $_POST[ $meta_key ];
            if ( $def[1] === 'number' ) {
                if ( $raw === '' ) { delete_post_meta( $post_id, $meta_key ); }
                else { update_post_meta( $post_id, $meta_key, intval( $raw ) ); }
            } else {
                update_post_meta( $post_id, $meta_key, sanitize_text_field( $raw ) );
            }
        }
    }

    // Traduções
    foreach ( array( 'titulo_en', 'titulo_ja' ) as $k ) {
        if ( isset( $_POST[ $k ] ) ) update_post_meta( $post_id, $k, sanitize_text_field( $_POST[ $k ] ) );
    }
    foreach ( array( 'texto_en', 'texto_ja' ) as $k ) {
        if ( isset( $_POST[ $k ] ) ) update_post_meta( $post_id, $k, wp_kses_post( $_POST[ $k ] ) );
    }
}

// -------------------------------------------------------------------------
// 4. MIGRAÇÃO: cursos antigos (post na categoria "cursos") → produto tipo=curso
// -------------------------------------------------------------------------
// Idempotente e não-destrutiva: cria o produto, guarda `_migrado_de` para não
// duplicar, e coloca o post original como rascunho (some do portfólio sem
// ser apagado). Acionada por botão em Loja ▸ Migrar cursos.
// -------------------------------------------------------------------------
add_action( 'admin_menu', function () {
    add_submenu_page( 'edit.php?post_type=produto', 'Migrar cursos', 'Migrar cursos', 'manage_options', 'migrar-cursos', 'tiete_render_pagina_migracao' );
} );

function tiete_render_pagina_migracao() {
    $feito = false;
    if ( isset( $_POST['tiete_migrar'] ) && check_admin_referer( 'tiete_migrar_cursos' ) ) {
        $res   = tiete_migrar_cursos();
        $feito = true;
    }

    // Prévia: quantos cursos ainda não migrados existem
    $pendentes = tiete_cursos_pendentes();
    ?>
    <div class="wrap">
        <h1>Migrar cursos para a Loja</h1>
        <p>Move os posts da categoria <strong>Cursos</strong> para o novo tipo <strong>Produto → Curso</strong>, copiando título, conteúdo, imagem destacada, traduções, dados do curso e o handle do Shopify. O post original vira <em>rascunho</em> (não é apagado).</p>
        <?php if ( $feito ) : ?>
            <div class="notice notice-success"><p>
                Migração concluída: <strong><?php echo intval( $res['criados'] ); ?></strong> criados,
                <strong><?php echo intval( $res['pulados'] ); ?></strong> já existentes (pulados).
            </p></div>
        <?php endif; ?>
        <p><strong><?php echo count( $pendentes ); ?></strong> curso(s) pendente(s) de migração.</p>
        <form method="post">
            <?php wp_nonce_field( 'tiete_migrar_cursos' ); ?>
            <p><button type="submit" name="tiete_migrar" value="1" class="button button-primary"<?php disabled( empty( $pendentes ) ); ?>>Migrar agora</button></p>
        </form>
    </div>
    <?php
}

/** Posts na categoria 'cursos' que ainda não têm produto correspondente. */
function tiete_cursos_pendentes() {
    $q = new WP_Query( array(
        'post_type'      => 'post',
        'category_name'  => 'cursos',
        'post_status'    => array( 'publish', 'draft', 'pending' ),
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
    ) );
    $ja = get_posts( array(
        'post_type'      => 'produto',
        'post_status'    => 'any',
        'posts_per_page' => -1,
        'no_found_rows'  => true,
        'fields'         => 'ids',
        'meta_key'       => '_migrado_de',
    ) );
    $origens_migradas = array_map( function ( $pid ) { return (int) get_post_meta( $pid, '_migrado_de', true ); }, $ja );
    return array_values( array_diff( $q->posts, $origens_migradas ) );
}

/** Executa a migração dos cursos pendentes. Retorna contadores. */
function tiete_migrar_cursos() {
    $criados = 0; $pulados = 0;
    $pendentes = tiete_cursos_pendentes();

    foreach ( $pendentes as $origem_id ) {
        $origem = get_post( $origem_id );
        if ( ! $origem ) { $pulados++; continue; }

        $novo_id = wp_insert_post( array(
            'post_type'    => 'produto',
            'post_status'  => $origem->post_status === 'publish' ? 'publish' : 'draft',
            'post_title'   => $origem->post_title,
            'post_content' => $origem->post_content,
            'post_excerpt' => $origem->post_excerpt,
            'post_name'    => $origem->post_name,
        ), true );

        if ( is_wp_error( $novo_id ) ) { $pulados++; continue; }

        wp_set_object_terms( $novo_id, 'curso', 'tipo_produto', false );

        // Imagem destacada
        $thumb = get_post_thumbnail_id( $origem_id );
        if ( $thumb ) set_post_thumbnail( $novo_id, $thumb );

        // Handle Shopify (campo antigo → novo)
        $handle = get_post_meta( $origem_id, 'curso_shopify_produto', true );
        if ( $handle ) update_post_meta( $novo_id, 'shopify_handle', $handle );

        // Dados de curso + traduções + CTA
        $copiar = array(
            'curso_professor', 'curso_data_hora', 'curso_duracao', 'curso_vagas',
            'titulo_en', 'titulo_ja', 'texto_en', 'texto_ja',
        );
        foreach ( $copiar as $mk ) {
            $v = get_post_meta( $origem_id, $mk, true );
            if ( $v !== '' ) update_post_meta( $novo_id, $mk, $v );
        }
        $cta = get_post_meta( $origem_id, 'curso_cta_url', true );
        if ( $cta ) update_post_meta( $novo_id, 'produto_cta_url', $cta );

        // Marca vínculo e arquiva o original
        update_post_meta( $novo_id, '_migrado_de', $origem_id );
        wp_update_post( array( 'ID' => $origem_id, 'post_status' => 'draft' ) );

        $criados++;
    }

    if ( $criados ) flush_rewrite_rules();
    return array( 'criados' => $criados, 'pulados' => $pulados );
}

// -------------------------------------------------------------------------
// 5. RENDERIZAÇÃO DA FICHA (usada pela sidebar) — mantém o design atual
// -------------------------------------------------------------------------
/**
 * Campos-manchete de cada tipo, exibidos na linha de meta (estilo autoria).
 * O restante dos campos aparece no detalhe da ficha.
 */
function tiete_produto_manchete_por_tipo() {
    return array(
        'curso'  => array( 'curso_professor', 'curso_data_hora', 'curso_duracao' ),
        'livro'  => array( 'livro_autor', 'livro_editora', 'livro_ano' ),
        'objeto' => array( 'objeto_material', 'objeto_dimensoes', 'objeto_edicao' ),
    );
}

/** Formata o valor de um campo para exibição (datetime vira dd/mm/aaaa · HHhMM). */
function tiete_produto_valor_exibicao( $meta_key, $valor, $html_tipo ) {
    if ( $valor === '' || $valor === null ) return '';
    if ( $html_tipo === 'datetime-local' ) {
        $ts = strtotime( $valor );
        return $ts ? ( date_i18n( 'd/m/Y', $ts ) . ' · ' . date_i18n( 'H\hi', $ts ) ) : $valor;
    }
    return $valor;
}

/**
 * Renderiza a ficha de um produto na barra lateral (single-produto).
 * Reaproveita as classes do layout de curso para não mudar o visual.
 */
function tiete_render_ficha_produto( $post_id, $lang, $textos ) {
    $tipo   = tiete_produto_tipo( $post_id );
    $campos = tiete_produto_campos_por_tipo();
    if ( ! $tipo || empty( $campos[ $tipo ] ) ) return; // sem tipo, nada a vender

    $defs      = $campos[ $tipo ];
    $manchetes = tiete_produto_manchete_por_tipo()[ $tipo ] ?? array();

    // Título traduzido
    $titulo_custom = $lang !== 'pt' ? get_post_meta( $post_id, 'titulo_' . $lang, true ) : '';
    $titulo        = ! empty( $titulo_custom ) ? $titulo_custom : get_the_title( $post_id );

    // Shopify: preço/estoque ao vivo
    $handle  = get_post_meta( $post_id, 'shopify_handle', true );
    $pronto  = tiete_shopify_pronto();
    $info    = ( $pronto && $handle ) ? tiete_shopify_info_produto( $handle ) : array( 'ok' => false );
    $preco   = $info['ok'] ? $info['preco_formatado'] : '';
    $disp    = $info['ok'] ? ! empty( $info['disponivel'] ) : true;
    $cta_url = get_post_meta( $post_id, 'produto_cta_url', true );

    // Vagas (só curso)
    $vagas = $tipo === 'curso' ? get_post_meta( $post_id, 'curso_vagas', true ) : '';

    // Descrição traduzida (ou conteúdo)
    $texto_trad = $lang !== 'pt' ? get_post_meta( $post_id, 'texto_' . $lang, true ) : '';
    ?>
    <div class="ficha-tecnica ficha-tecnica--curso ficha-tecnica--produto" data-tipo="<?php echo esc_attr( $tipo ); ?>">
        <h1 class="titulo-projeto-destaque"><?php echo esc_html( $titulo ); ?></h1>

        <?php
        // Linha de manchete (professor/autor/material · etc.)
        $partes = array();
        foreach ( $manchetes as $mk ) {
            if ( empty( $defs[ $mk ] ) ) continue;
            $v = tiete_produto_valor_exibicao( $mk, get_post_meta( $post_id, $mk, true ), $defs[ $mk ][1] );
            if ( $v !== '' ) $partes[] = $v;
        }
        if ( $partes ) : ?>
            <div class="autoria-projeto curso-meta-linha">
                <?php foreach ( $partes as $p ) : ?><span><?php echo esc_html( $p ); ?></span><?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="texto-descricao-lateral">
            <div class="texto-descricao-interno">
                <?php
                if ( ! empty( $texto_trad ) ) {
                    echo wpautop( $texto_trad );
                } else {
                    echo apply_filters( 'the_content', preg_replace( '/<img\b[^>]*>/i', '', get_post_field( 'post_content', $post_id ) ) );
                }
                ?>

                <?php
                // Detalhe da ficha: todos os campos do tipo com valor (rótulos em PT por ora)
                $linhas = array();
                foreach ( $defs as $mk => $def ) {
                    $v = tiete_produto_valor_exibicao( $mk, get_post_meta( $post_id, $mk, true ), $def[1] );
                    if ( $v !== '' ) $linhas[] = array( $def[0], $v );
                }
                if ( $linhas ) : ?>
                    <dl class="produto-ficha-detalhe">
                        <?php foreach ( $linhas as $l ) : ?>
                            <dt><?php echo esc_html( $l[0] ); ?></dt><dd><?php echo esc_html( $l[1] ); ?></dd>
                        <?php endforeach; ?>
                    </dl>
                <?php endif; ?>
            </div>
        </div>

        <div class="curso-compra-box">
            <?php if ( $pronto && $handle && ! $disp ) : ?>
                <span class="btn-inscrever-se btn-inscrever-se--em-breve"><?php echo esc_html( $textos['esgotado'] ); ?></span>
            <?php elseif ( $pronto && $handle ) : ?>
                <button type="button" class="btn-inscrever-se btn-comprar-shopify" data-produto="<?php echo esc_attr( $handle ); ?>"><?php echo esc_html( $textos['comprar'] ); ?></button>
            <?php elseif ( $cta_url ) : ?>
                <a href="<?php echo esc_url( $cta_url ); ?>" class="btn-inscrever-se" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $textos['comprar'] ); ?></a>
            <?php else : ?>
                <span class="btn-inscrever-se btn-inscrever-se--em-breve"><?php echo esc_html( $textos['comprar'] ); ?></span>
            <?php endif; ?>

            <div class="curso-compra-info">
                <?php if ( $preco || $vagas !== '' ) : ?>
                    <div class="curso-preco-linha">
                        <?php if ( $preco ) : ?><span class="curso-preco"><?php echo esc_html( $preco ); ?></span><?php endif; ?>
                        <?php if ( $vagas !== '' ) : ?><span class="curso-vagas"><?php echo esc_html( $vagas ); ?> <?php echo esc_html( $textos['vagas_label'] ); ?></span><?php endif; ?>
                    </div>
                <?php endif; ?>
                <?php if ( $pronto && $handle && $tipo === 'curso' && defined( 'SHOPIFY_CUPOM_ESTUDANTE' ) && SHOPIFY_CUPOM_ESTUDANTE ) : ?>
                    <label class="curso-check-estudante">
                        <input type="checkbox" class="curso-input-estudante">
                        <?php echo esc_html( $textos['meia_estudante'] ); ?>
                    </label>
                <?php endif; ?>
            </div>

            <span class="curso-compra-erro" aria-live="polite"></span>
        </div>
    </div>
    <?php
}

/**
 * Lista de produtos para a barra lateral (vitrine tipo "lista de projetos").
 * Opcionalmente filtra por tipo (slug).
 */
function tiete_render_lista_produtos( $lang, $tipo_slug = '' ) {
    $args = array(
        'post_type'      => 'produto',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true,
    );
    if ( $tipo_slug ) {
        $args['tax_query'] = array( array(
            'taxonomy' => 'tipo_produto',
            'field'    => 'slug',
            'terms'    => $tipo_slug,
        ) );
    }
    $q = new WP_Query( $args );
    echo '<nav class="menu-projetos"><ul id="listaProdutos">';
    while ( $q->have_posts() ) {
        $q->the_post();
        $pid   = get_the_ID();
        $t_cus = $lang !== 'pt' ? get_post_meta( $pid, 'titulo_' . $lang, true ) : '';
        $t     = ! empty( $t_cus ) ? $t_cus : get_the_title();
        $link  = tiete_url_com_lang( get_permalink(), $lang );
        printf(
            '<li class="item-projeto" data-projeto-id="%d"><a href="%s">%s</a></li>',
            $pid, esc_url( $link ), esc_html( $t )
        );
    }
    echo '</ul></nav>';
    wp_reset_postdata();
}
