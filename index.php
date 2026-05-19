<?php get_header(); ?>
<?php
$lang = tiete_get_lang();
$textos = tiete_get_dicionario($lang);
?>
<main class="area-scroll" id="mainContent">
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
