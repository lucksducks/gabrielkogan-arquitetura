<?php get_header(); ?>
<?php
$lang = tiete_get_lang();
$textos = tiete_get_dicionario($lang);

$page_sobre = get_page_by_path('sobre'); 
$texto_sobre_final = '';

if ($page_sobre) {
    if ($lang !== 'pt') {
        $texto_traduzido = get_post_meta($page_sobre->ID, 'conteudo_' . $lang, true);
        $texto_sobre_final = ! empty($texto_traduzido) ? wpautop($texto_traduzido) : apply_filters('the_content', $page_sobre->post_content);
    } else {
        $texto_sobre_final = apply_filters('the_content', $page_sobre->post_content);
    }
}
?>

<div id="introOverlay" class="intro-overlay">
    <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/hanko.png" alt="Hanko" class="img-hanko-intro">
</div>

<main class="area-scroll" id="mainContent">
    <nav class="snap-dots" id="scrollIndicator">
        <button class="snap-dot snap-dot--ativo" data-index="0" aria-label="<?php echo esc_attr( $textos['capa'] ); ?>"></button>
        <button class="snap-dot" data-index="1" aria-label="<?php echo esc_attr( $textos['pratica_tit'] ); ?>"></button>
        <button class="snap-dot" data-index="2" aria-label="<?php echo esc_attr( $textos['yayoi'] ); ?>"></button>
        <button class="snap-dot" data-index="3" aria-label="<?php echo esc_attr( $textos['album_semana'] ); ?>"></button>
    </nav>

    <?php
    $categoria_mobile = isset( $_GET['categoria'] ) ? sanitize_key( wp_unslash( $_GET['categoria'] ) ) : '';
    $ids_mobile = tiete_get_projetos_home_mobile_curados();
    $args_mobile = array(
        'post_type' => 'post',
        'posts_per_page' => $categoria_mobile ? -1 : 5,
        'no_found_rows' => true,
        'ignore_sticky_posts' => true,
    );
    if ( $categoria_mobile ) {
        $args_mobile['category_name'] = $categoria_mobile;
        $args_mobile['orderby'] = 'title';
        $args_mobile['order'] = 'ASC';
    } elseif ( $ids_mobile ) {
        $args_mobile['post__in'] = $ids_mobile;
        $args_mobile['orderby'] = 'post__in';
    } else {
        $args_mobile['orderby'] = 'title';
        $args_mobile['order'] = 'ASC';
    }
    $projetos_mobile = new WP_Query( $args_mobile );
    ?>
    <section class="home-mobile-projects" aria-label="<?php echo esc_attr( $textos['projetos'] ); ?>">
        <?php while ( $projetos_mobile->have_posts() ) : $projetos_mobile->the_post();
            if ( ! has_post_thumbnail() ) continue;
            $titulo_mobile = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'titulo_' . $lang, true ) : '';
            $titulo_mobile = $titulo_mobile ?: get_the_title();
            $url_mobile = get_permalink();
            if ( $categoria_mobile ) $url_mobile = add_query_arg( 'categoria', $categoria_mobile, $url_mobile );
            $url_mobile = tiete_url_com_lang( $url_mobile, $lang );
            ?>
            <a class="home-mobile-project" href="<?php echo esc_url( $url_mobile ); ?>" aria-label="<?php echo esc_attr( $titulo_mobile ); ?>">
                <?php echo get_the_post_thumbnail( get_the_ID(), 'full', array( 'alt' => '', 'loading' => 'lazy', 'decoding' => 'async', 'sizes' => '(max-width: 1024px) 100vw, 1px' ) ); ?>
            </a>
        <?php endwhile; wp_reset_postdata(); ?>
    </section>

    <div class="area-scroll-thumbs">
        <div id="prevHover">
            <?php
            $todos_projetos = new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true) );
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
            ?>
        </div>
    </div>

    <section id="secaoCapa" class="secao-snap capa-estatica">
        <div class="capa-imagem-wrapper home-step-frame">
            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/home.png" alt="Gabriel Kogan Arquitetura" class="img-capa-full" loading="lazy">
        </div>
    </section>

    <div class="wrap-projetos" style="display: none;"></div>

    <section id="secaoSobre" class="secao-sobre-home secao-snap">
        <div class="conteudo-sobre home-step-frame">
            <div class="sobre-texto">
                <h2><?php echo esc_html( $textos['pratica_tit'] ); ?></h2>

                <div class="sobre-texto-scroll" data-lenis-prevent>
                    <?php echo $texto_sobre_final; ?>
                </div>

            </div>

            <div class="sobre-contato">
                <h3><?php echo esc_html( $textos['contato'] ); ?></h3>
                <p>
                    <strong>SÃO PAULO</strong><br>
                    Alameda Tietê, 178 - Jardins<br>
                    <a href="mailto:gabrielkogan@gabrielkogan.com">gabrielkogan@gabrielkogan.com</a><br>
                    © <?php echo esc_html( date('Y') ); ?>
                </p>
            </div>
        </div>
    </section>
    
    <section id="secaoYayoi" class="secao-yayoi-home secao-snap">
        <div class="yayoi-container home-step-frame">
            <div class="yayoi-grafico-area">
                <div class="yayoi-eixo-x">
                    <span class="label-esq"><?php echo esc_html( $textos['vermelho'] ); ?></span>
                    <span class="label-dir"><?php echo esc_html( $textos['branco'] ); ?></span>
                </div>
                <div class="yayoi-eixo-y">
                    <span class="label-topo"><?php echo esc_html( $textos['universal'] ); ?></span>
                    <span class="label-base"><?php echo esc_html( $textos['privado'] ); ?></span>
                </div>

                <div class="yayoi-pontos-wrapper">
                    <?php
                    // Reaproveitamos a query de projetos que já corre no topo da página
                    if ( $todos_projetos->have_posts() ) :
                        while ( $todos_projetos->have_posts() ) : $todos_projetos->the_post();
                            $x = get_post_meta( get_the_ID(), 'yayoi_eixo_x', true );
                            $y = get_post_meta( get_the_ID(), 'yayoi_eixo_y', true );

                            // O PHP só desenha a "bolinha" se o projeto tiver o X e o Y definidos no painel
                            if ( $x !== '' && $y !== '' ) :
                                $link_projeto = tiete_url_com_lang( get_permalink(), $lang );
                                $titulo_traduzido = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'titulo_' . $lang, true ) : '';
                                $titulo = ! empty( $titulo_traduzido ) ? $titulo_traduzido : get_the_title();
                                ?>
                                <a href="<?php echo esc_url($link_projeto); ?>" 
                                   class="yayoi-ponto" 
                                   style="left: <?php echo esc_attr($x); ?>%; bottom: <?php echo esc_attr($y); ?>%;"
                                   data-projeto-id="<?php echo get_the_ID(); ?>"
                                   aria-label="<?php echo esc_attr($titulo); ?>"></a>
                            <?php
                            endif;
                        endwhile;
                        $todos_projetos->rewind_posts(); // Rebina a cassete da query para o caso de ser usada novamente abaixo
                    endif;
                    ?>
                </div>
            </div>
        </div>
    </section>
    
    <?php get_template_part('template-parts/section', 'album'); ?>
</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>
