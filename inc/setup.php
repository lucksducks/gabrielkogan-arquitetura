<?php
// Habilita o suporte a Imagens Destacadas no Tema
add_action( 'after_setup_theme', function() {
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'title-tag' ); // Boa prática — deixa o WP gerenciar o <title>
    add_image_size( 'preview-lateral', 800, 800, true );
});

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
        'lang'         => (isset($_GET['lang']) && $_GET['lang'] === 'en') ? 'en' : 'pt',
        'homeUrl'      => esc_url( home_url('/') ),
        'ajaxUrl'      => admin_url('admin-ajax.php'),
        'albumArquivo' => function_exists('tiete_get_arquivo_leve') ? tiete_get_arquivo_leve() : [],
    ));
}

// =========================================================================
// DICIONÁRIO E TEXTOS DA INTERFACE
// =========================================================================
function tiete_get_dicionario($lang = 'pt') {
    $dicionario = [
        'pt' => [
            'sobre_hover' => 'SOBRE',
            'pratica_tit' => 'NOSSA PRÁTICA',
            'contato'     => 'CONTATO',
            'arq_subtit'  => 'ARQUITETURA',
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
            'pratica_tit' => 'OUR PRACTICE',
            'contato'     => 'CONTACT',
            'arq_subtit'  => 'ARCHITECTURE',
            'filtros'     => [
                'architecture' => 'Architecture',
                'design'       => 'Design',
                'research'     => 'Research',
                'courses'      => 'Courses',
                'photography'  => 'Photography',
                'cinema'       => 'Cinema',
            ]
        ]
    ];
    return $dicionario[$lang];
}