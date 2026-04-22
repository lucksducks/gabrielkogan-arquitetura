<?php get_header(); ?>
<?php
$lang = ( isset( $_GET['lang'] ) && $_GET['lang'] === 'en' ) ? 'en' : 'pt';
$textos = tiete_get_dicionario($lang);
?>
<main class="area-scroll" id="mainContent">
    <div class="logo-watermark-box" id="logoEasterEgg" style="cursor: pointer;">
        <div class="link-logo-vertical">
            <img src="<?php echo esc_url( get_template_directory_uri() ); ?>/assets/img/logo.png" alt="Gabriel Kogan" class="img-logo-pequena">
            <div class="bloco-texto-logo">
                <span class="nome-principal">GABRIEL KOGAN</span>
                <span class="subtitulo-arquitetura"><?php echo esc_html( $textos['arq_subtit'] ); ?></span>
            </div>
        </div>
    </div>

    <div class="area-scroll-thumbs">
        <div id="prevHover">
            <?php
            if ( have_posts() ) :
                while ( have_posts() ) : the_post();
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
</main>

<?php get_sidebar(); ?>
<?php get_footer(); ?>