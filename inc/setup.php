<?php
// Habilita o suporte a Imagens Destacadas no Tema
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' ); // Boa prática — deixa o WP gerenciar o <title>
    add_image_size( 'preview-lateral', 800, 800, true );
});

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
    wp_enqueue_style('tiete-style', get_stylesheet_uri(), array(), '24.1');

    // 3. Script do Lenis (Smooth Scroll)
    wp_enqueue_script('lenis', 'https://unpkg.com/lenis@1.1.13/dist/lenis.min.js', array(), '1.1.13', true);

    // 4. Nosso script principal (main.js) - Atualizado para pasta assets/
    wp_enqueue_script('tiete-main', get_template_directory_uri() . '/assets/js/main.js', array('lenis'), '24.1', true);

    // 5. Segurança: Passando variáveis do PHP para o JS de forma limpa e sanitizada
    wp_localize_script('tiete-main', 'temaConfig', array(
        'filtroAtivo'  => isset($_GET['categoria']) ? sanitize_key($_GET['categoria']) : '',
        'lang'         => tiete_get_lang(),
        'homeUrl'      => esc_url( home_url('/') ),
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'albumArquivo' => function_exists('tiete_get_arquivo_leve') ? tiete_get_arquivo_leve() : [],
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
            'filtros'     => [
                'architecture' => 'Architecture',
                'design'       => 'Design',
                'research'     => 'Research',
                'courses'      => 'Courses',
                'photography'  => 'Photography',
                'cinema'       => 'Cinema',
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
            'filtros'     => [
                'architecture' => '建築',
                'design'       => 'デザイン',
                'research'     => 'リサーチ',
                'courses'      => '講座',
                'photography'  => '写真',
                'cinema'       => '映画',
            ]
        ]
    ];
    return $dicionario[$lang] ?? $dicionario['pt'];
}
