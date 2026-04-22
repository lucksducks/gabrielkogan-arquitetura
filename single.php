<?php get_header(); ?>
<main class="area-scroll" id="mainContent">
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