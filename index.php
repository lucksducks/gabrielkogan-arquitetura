<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
    <meta charset="<?php bloginfo( 'charset' ); ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" href="<?php echo esc_url( get_template_directory_uri() ); ?>/img/icon.png" type="image/png">
    <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>

<div id="barraZoom" data-bg-zoom-url="<?php echo is_single() ? esc_url(get_post_meta(get_the_ID(), 'bg_zoom_image', true)) : ''; ?>" style="background-image: url('<?php echo is_single() && get_post_meta(get_the_ID(), 'bg_zoom_image', true) ? esc_url(get_post_meta(get_the_ID(), 'bg_zoom_image', true)) : esc_url(get_template_directory_uri()) . '/img/bg-zoom.jpg'; ?>');"></div>

<div id="easterEggTrigger" style="position: fixed; bottom: 0; left: 0; width: 60px; height: 60px; z-index: 9999;"></div>

<?php
    // =========================================================================
    // IDIOMA E TEXTOS DA INTERFACE (Refatorado - DRY)
    // =========================================================================
    $lang     = ( isset( $_GET['lang'] ) && $_GET['lang'] === 'en' ) ? 'en' : 'pt';
    $url_base = home_url('/');

    // 1. Dicionário Centralizado: Tudo fica em um lugar só
    $dicionario = [
        'pt' => [
            'sobre_hover' => 'SOBRE',
            'pratica_tit' => 'NOSSA PRÁTICA',
            'pratica_p1'  => 'Nossa prática de arquitetura xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.',
            'pratica_p2'  => 'xxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxx xxxxxxxxxxxxx xxx xxxxxxxxxx xxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxx xxx x x xxxxxxxxxxxxxxxxxx.',
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
            'pratica_p1'  => 'Our architectural practice xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx.',
            'pratica_p2'  => 'xxxxxxxxxxxxxxxx xxxxxxxxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxx xxxxxxxxxxxxx xxx xxxxxxxxxx xxxxxxxxxxxxxxxxx xxxxxxxxxxxxxxx xxx x x xxxxxxxxxxxxxxxxxx.',
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

    // 2. Atribuição Dinâmica das Variáveis (O PHP escolhe o pacote de textos certo)
    $txt_sobre_hover = $dicionario[$lang]['sobre_hover'];
    $txt_pratica_tit = $dicionario[$lang]['pratica_tit'];
    $txt_pratica_p1  = $dicionario[$lang]['pratica_p1'];
    $txt_pratica_p2  = $dicionario[$lang]['pratica_p2'];
    $txt_contato     = $dicionario[$lang]['contato'];
    $txt_arq_subtit  = $dicionario[$lang]['arq_subtit'];
    $filtros         = $dicionario[$lang]['filtros'];

    // 3. Lógica do botão de trocar idioma
    $link_toggle_lang = ( $lang === 'en' ) ? remove_query_arg('lang') : add_query_arg('lang', 'en');

    // =========================================================================
    // CONSULTA AO BANCO DE DADOS
    // =========================================================================
    $cat_slug = isset( $_GET['categoria'] ) ? sanitize_key( $_GET['categoria'] ) : '';

    $todos_projetos = new WP_Query( array(
        'post_type'      => 'post',
        'posts_per_page' => -1,
        'orderby'        => 'title',
        'order'          => 'ASC',
        'no_found_rows'  => true, // Otimização de performance: desliga paginação
    ) );
?>

<?php if ( is_front_page() ) : ?>
<div id="introOverlay" class="intro-overlay">
    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/hanko.png" alt="Hanko" class="img-hanko-intro">
</div>
<?php endif; ?>

<div class="container-principal">
    <div class="scroll-indicator" id="scrollIndicator">
        <span class="scroll-text"><?php echo esc_html( $txt_sobre_hover ); ?></span>
        <div class="scroll-arrow"></div>
    </div>

    <main class="area-scroll" id="mainContent">

        <div class="logo-watermark-box" id="logoEasterEgg" style="cursor: pointer;">
            <div class="link-logo-vertical">
                <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/img/logo.png" alt="Gabriel Kogan" class="img-logo-pequena">
                <div class="bloco-texto-logo">
                    <span class="nome-principal">GABRIEL KOGAN</span>
                    <span class="subtitulo-arquitetura"><?php echo esc_html( $txt_arq_subtit ); ?></span>
                </div>
            </div>
        </div>

        <div class="area-scroll-thumbs">
            <div id="prevHover">
                <?php
                if ( $todos_projetos->have_posts() ) :
                    while ( $todos_projetos->have_posts() ) : $todos_projetos->the_post();
                        if ( has_post_thumbnail() ) :
                            echo '<div class="prev-hover-img" data-projeto-id="' . get_the_ID() . '">';
                            the_post_thumbnail( 'large' );
                            echo '</div>';
                        endif;
                    endwhile;
                    wp_reset_postdata();
                endif;
                $todos_projetos->rewind_posts();
                ?>
            </div>
        </div>

        <div class="wrap-projetos">
            <?php
            if ( have_posts() ) :
                while ( have_posts() ) : the_post(); ?>
                    <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
                        <div class="conteudo-projeto">
                            <?php the_content(); ?>
                        </div>
                    </article>
                <?php endwhile;
            endif; ?>
        </div>

        <?php if ( is_front_page() ) : ?>
        <section id="secaoSobre" class="secao-sobre-home">
            <div class="conteudo-sobre">
                <div class="sobre-texto">
                    <h2><?php echo esc_html( $txt_pratica_tit ); ?></h2>
                    <p><?php echo esc_html( $txt_pratica_p1 ); ?></p>
                    <p><?php echo esc_html( $txt_pratica_p2 ); ?></p>
                </div>
                <div class="sobre-contato">
                    <h3><?php echo esc_html( $txt_contato ); ?></h3>
                    <p>
                        <strong>SÃO PAULO</strong><br>
                        Alameda Tietê, 178 - Jardins<br>
                        <a href="mailto:gabrielkogan@gabrielkogan.com">gabrielkogan@gabrielkogan.com</a><br>
                        © <?php echo esc_html( date('Y') ); ?>
                    </p>
                </div>
            </div>
        </section>
        <?php endif; ?>
    </main>

    <aside class="barra-fixa">
        <div class="navegacao-topo">

            <?php $link_home_logo = ( $lang === 'en' ) ? add_query_arg( 'lang', 'en', $url_base ) : $url_base; ?>
            <a href="<?php echo esc_url( $link_home_logo ); ?>" class="bloco-texto-logo marca-topo-direito btn-home-ajax">
                <span class="nome-principal">GABRIEL KOGAN</span>
                <span class="subtitulo-arquitetura"><?php echo esc_html( $txt_arq_subtit ); ?></span>
            </a>

            <nav class="filtros-categoria">
                <ul>
                    <?php foreach ( $filtros as $slug => $nome ) :
                        $classe_ativo = ( $cat_slug === $slug ) ? 'filtro-ativo' : '';
                        $link_filtro  = add_query_arg( 'categoria', $slug, $url_base );
                        if ( $lang === 'en' ) $link_filtro = add_query_arg( 'lang', 'en', $link_filtro );
                        ?>
                        <li>
                            <a href="<?php echo esc_url( $link_filtro ); ?>"
                               class="<?php echo esc_attr( $classe_ativo ); ?>"
                               data-slug="<?php echo esc_attr( $slug ); ?>">
                                <?php echo esc_html( $nome ); ?>
                            </a>
                        </li>
                    <?php endforeach; ?>
                    <li><a href="https://tiete178lab.com" target="_blank" rel="noopener noreferrer" class="link-externo">TIETÊ178</a></li>
                    <li>
                        <a href="<?php echo esc_url( $link_toggle_lang ); ?>" class="btn-idioma">
                            <span style="color:<?php echo $lang === 'pt' ? '#7b7b7b' : '#b5b4af'; ?>;font-weight:<?php echo $lang === 'pt' ? 'bold' : 'normal'; ?>;">PT</span><span style="color:<?php echo $lang === 'en' ? '#7b7b7b' : '#b5b4af'; ?>;font-weight:<?php echo $lang === 'en' ? 'bold' : 'normal'; ?>;">EN</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="container-dinamico-lateral">
            <?php if ( is_single() ) :
                $titulo_custom   = get_post_meta( get_the_ID(), 'titulo_en', true );
                $titulo_exibicao = ( $lang === 'en' && ! empty( $titulo_custom ) ) ? $titulo_custom : get_the_title();
                $autoria_pt      = get_post_meta( get_the_ID(), 'autoria_pt', true );
                $autoria_en      = get_post_meta( get_the_ID(), 'autoria_en', true );
                $autoria_exibicao = ( $lang === 'en' && ! empty( $autoria_en ) ) ? $autoria_en : $autoria_pt;
            ?>
                <div class="ficha-tecnica">
                    <h1 class="titulo-projeto-destaque"><?php echo esc_html( $titulo_exibicao ); ?></h1>
                    <?php if ( ! empty( $autoria_exibicao ) ) : ?>
                        <div class="autoria-projeto"><?php echo nl2br( esc_html( $autoria_exibicao ) ); ?></div>
                    <?php endif; ?>
                    <div class="texto-descricao-lateral">
                        <div class="texto-descricao-interno">
                            <?php
                            $texto_en = get_post_meta( get_the_ID(), 'texto_en', true );
                            if ( $lang === 'en' && ! empty( $texto_en ) ) {
                                echo wpautop( $texto_en );
                            } else {
                                $content = get_the_content();
                                echo apply_filters('the_content', preg_replace('/<img\b[^>]*>/i', '', $content));
                            }
                            ?>
                        </div>
                    </div>
                </div>
            <?php else : ?>
                <nav class="menu-projetos">
                    <ul id="listaProjetos">
                        <?php
                        $query_lista = ( $cat_slug ) ? new WP_Query( array(
                            'post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true, 'category_name' => $cat_slug,
                        ) ) : $todos_projetos;

                        if ( $query_lista->have_posts() ) :
                            while ( $query_lista->have_posts() ) : $query_lista->the_post();
                                $link_projeto = get_permalink();
                                if ( $cat_slug ) $link_projeto = add_query_arg( 'categoria', $cat_slug, $link_projeto );
                                if ( $lang === 'en' ) $link_projeto = add_query_arg( 'lang', 'en', $link_projeto );
                                $tit_lista_en = get_post_meta( get_the_ID(), 'titulo_en', true );
                                $tit_lista    = ( $lang === 'en' && ! empty( $tit_lista_en ) ) ? $tit_lista_en : get_the_title();
                                ?>
                                <li class="item-projeto" data-projeto-id="<?php echo get_the_ID(); ?>">
                                    <a href="<?php echo esc_url( $link_projeto ); ?>"><?php echo esc_html( $tit_lista ); ?></a>
                                </li>
                            <?php endwhile;
                        endif;
                        
                        if ($cat_slug) wp_reset_postdata();
                        ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </aside>
</div>

<?php wp_footer(); ?>
</body>
</html>