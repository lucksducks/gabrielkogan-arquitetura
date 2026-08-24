<?php
// Habilita o suporte a Imagens Destacadas no Tema
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' ); // Boa prática — deixa o WP gerenciar o <title>
    add_image_size( 'preview-lateral', 800, 800, true );

    // Curadoria dos projetos na barra lateral da home: o admin monta este menu
    // (Aparência → Menus) arrastando os projetos na ordem desejada. Se vazio,
    // a sidebar cai no fallback (todos os projetos, ordem alfabética).
    register_nav_menus( array(
        'projetos_home' => 'Projetos da Home (barra lateral)',
    ) );
});

/**
 * IDs dos projetos curados para a barra lateral da home, na ordem do menu
 * 'projetos_home'. Retorna array vazio se o menu não existir ou estiver vazio
 * (a sidebar então usa o fallback: todos os projetos em ordem alfabética).
 */
function tiete_get_projetos_home_curados() {
    $locations = get_nav_menu_locations();
    if ( empty( $locations['projetos_home'] ) ) {
        return array();
    }

    $itens = wp_get_nav_menu_items( $locations['projetos_home'] ); // já vem ordenado
    if ( ! $itens ) {
        return array();
    }

    $ids = array();
    foreach ( $itens as $item ) {
        if ( $item->object === 'post' && (int) $item->object_id ) {
            $ids[] = (int) $item->object_id;
        }
    }
    return $ids;
}

function tiete_get_idiomas() {
    return array( 'pt', 'en', 'ja' );
}

function tiete_get_lang() {
    $lang = isset( $_GET['lang'] ) ? sanitize_key( wp_unslash( $_GET['lang'] ) ) : 'pt';
    if ( $lang === 'jp' ) {
        $lang = 'ja';
    }

    return in_array( $lang, tiete_get_idiomas(), true ) ? $lang : 'pt';
}

function tiete_url_com_lang( $url, $lang ) {
    if ( $lang === 'jp' ) {
        $lang = 'ja';
    }
    $lang = in_array( $lang, tiete_get_idiomas(), true ) ? $lang : 'pt';

    if ( $lang === 'pt' ) {
        return remove_query_arg( 'lang', $url );
    }

    return add_query_arg( 'lang', $lang, $url );
}

function tiete_get_html_lang() {
    $html_langs = array(
        'pt' => 'pt-BR',
        'en' => 'en',
        'ja' => 'ja',
    );

    return $html_langs[ tiete_get_lang() ];
}

// Carregamento de Scripts e Estilos
add_action('wp_enqueue_scripts', 'tiete_enqueue_scripts');
function tiete_enqueue_scripts() {
    // 1. Google Fonts
    wp_enqueue_style('google-fonts-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;700&display=swap', array(), null);

    // 2. Estilo principal do tema (style.css)
    wp_enqueue_style('tiete-style', get_stylesheet_uri(), array(), '25.3');

    // 3. Script do Lenis (Smooth Scroll)
    wp_enqueue_script('lenis', 'https://unpkg.com/lenis@1.1.13/dist/lenis.min.js', array(), '1.1.13', true);

    // 4. Nosso script principal (main.js) - Atualizado para pasta assets/
    wp_enqueue_script('tiete-main', get_template_directory_uri() . '/assets/js/main.js', array('lenis'), '25.3', true);

    // 5. Segurança: Passando variáveis do PHP para o JS de forma limpa e sanitizada
    wp_localize_script('tiete-main', 'temaConfig', array(
        'filtroAtivo'    => isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '',
        'lang'           => tiete_get_lang(),
        'homeUrl'        => esc_url( home_url('/') ),
        'ajaxUrl'        => admin_url('admin-ajax.php'),
        'albumArquivo'   => function_exists('tiete_get_arquivo_leve') ? tiete_get_arquivo_leve() : [],
        // Shopify Storefront API — token PÚBLICO, seguro no JS. Fica vazio até ser configurado no wp-config.php.
        'shopifyDomain'  => defined('SHOPIFY_STORE_DOMAIN') ? SHOPIFY_STORE_DOMAIN : '',
        'shopifyToken'   => defined('SHOPIFY_STOREFRONT_TOKEN') ? SHOPIFY_STOREFRONT_TOKEN : '',
        'cupomEstudante' => defined('SHOPIFY_CUPOM_ESTUDANTE') ? SHOPIFY_CUPOM_ESTUDANTE : '',
        'erroCompra'     => tiete_get_dicionario( tiete_get_lang() )['erro_compra'],
        'carrinhoTextos' => array(
            'vazio'   => tiete_get_dicionario( tiete_get_lang() )['cart_vazio'],
            'remover' => tiete_get_dicionario( tiete_get_lang() )['cart_remover'],
        ),
    ));
}

// =========================================================================
// DICIONÁRIO E TEXTOS DA INTERFACE
// =========================================================================
function tiete_get_dicionario($lang = 'pt') {
    if ( $lang === 'jp' ) {
        $lang = 'ja';
    }

    $dicionario = [
        'pt' => [
            'sobre_hover' => 'SOBRE',
            'capa'        => 'Capa',
            'pratica_tit' => 'NOSSA PRÁTICA',
            'contato'     => 'CONTATO',
            'arq_subtit'  => 'ARQUITETURA',
            'yayoi'       => 'Yayoi',
            'album_semana'=> 'Album da Semana',
            'vermelho'    => 'Vermelho',
            'branco'      => 'Branco',
            'universal'   => 'Universal',
            'privado'     => 'Privado',
            'professor'   => 'Professor',
            'inscrever_se'=> 'Inscrever-se',
            'vagas_label' => 'vagas',
            'erro_compra' => 'Erro ao adicionar ao carrinho. Tente novamente.',
            'meia_estudante' => 'Sou estudante (meia-entrada)',
            'comprar'      => 'Adicionar ao carrinho',
            'esgotado'     => 'Esgotado',
            'carrinho'     => 'Carrinho',
            'seu_carrinho' => 'Seu carrinho',
            'subtotal'     => 'Subtotal',
            'frete_nota'   => 'Frete e impostos calculados no checkout.',
            'finalizar'    => 'Finalizar compra',
            'cart_vazio'   => 'Seu carrinho está vazio.',
            'cart_remover' => 'Remover',
            'filtros'     => [
                'arquitetura' => 'Arquitetura',
                'design'      => 'Design',
                'pesquisa'    => 'Pesquisa',
                'cursos'      => 'Cursos',
                'fotografia'  => 'Fotografia',
                'cinema'      => 'Cinema',
            ]
        ],
        'en' => [
            'sobre_hover' => 'ABOUT',
            'capa'        => 'Cover',
            'pratica_tit' => 'OUR PRACTICE',
            'contato'     => 'CONTACT',
            'arq_subtit'  => 'ARCHITECTURE',
            'yayoi'       => 'Yayoi',
            'album_semana'=> 'Album of the Week',
            'vermelho'    => 'Red',
            'branco'      => 'White',
            'universal'   => 'Universal',
            'privado'     => 'Private',
            'professor'   => 'Instructor',
            'inscrever_se'=> 'Enroll now',
            'vagas_label' => 'seats',
            'erro_compra' => 'Error adding to cart. Please try again.',
            'meia_estudante' => 'I\'m a student (half price)',
            'comprar'      => 'Add to cart',
            'esgotado'     => 'Sold out',
            'carrinho'     => 'Cart',
            'seu_carrinho' => 'Your cart',
            'subtotal'     => 'Subtotal',
            'frete_nota'   => 'Shipping & taxes calculated at checkout.',
            'finalizar'    => 'Checkout',
            'cart_vazio'   => 'Your cart is empty.',
            'cart_remover' => 'Remove',
            'filtros'     => [
                'arquitetura' => 'Architecture',
                'design'      => 'Design',
                'pesquisa'    => 'Research',
                'cursos'      => 'Courses',
                'fotografia'  => 'Photography',
                'cinema'      => 'Cinema',
            ]
        ],
        'ja' => [
            'sobre_hover' => '概要',
            'capa'        => 'カバー',
            'pratica_tit' => '私たちの実践',
            'contato'     => '連絡先',
            'arq_subtit'  => '建築',
            'yayoi'       => '弥生',
            'album_semana'=> '今週のアルバム',
            'vermelho'    => '赤',
            'branco'      => '白',
            'universal'   => '普遍的',
            'privado'     => '私的',
            'professor'   => '講師',
            'inscrever_se'=> '申し込む',
            'vagas_label' => '席',
            'erro_compra' => 'カートに追加できませんでした。もう一度お試しください。',
            'meia_estudante' => '学生です（学割）',
            'comprar'      => 'カートに追加',
            'esgotado'     => '売り切れ',
            'carrinho'     => 'カート',
            'seu_carrinho' => 'カート',
            'subtotal'     => '小計',
            'frete_nota'   => '送料・税は購入手続き時に計算されます。',
            'finalizar'    => '購入手続きへ',
            'cart_vazio'   => 'カートは空です。',
            'cart_remover' => '削除',
            'filtros'     => [
                'arquitetura' => '建築',
                'design'      => 'デザイン',
                'pesquisa'    => 'リサーチ',
                'cursos'      => '講座',
                'fotografia'  => '写真',
                'cinema'      => '映画',
            ]
        ]
    ];
    return $dicionario[$lang] ?? $dicionario['pt'];
}
