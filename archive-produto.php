<?php
// =========================================================================
// VITRINE DA LOJA — arquivo do CPT `produto` e das páginas de tipo.
// Layout provisório (design dedicado virá depois): reaproveita a estrutura
// area-scroll + sidebar. A sidebar lista os produtos (lista lateral); a área
// principal mostra as capas clicáveis.
// =========================================================================
get_header();
$lang = tiete_get_lang();
?>
<main class="area-scroll" id="mainContent">
    <div class="wrap-projetos loja-vitrine">
        <?php if ( have_posts() ) : while ( have_posts() ) : the_post();
            if ( ! has_post_thumbnail() ) continue;
            $pid  = get_the_ID();
            $t_cus = $lang !== 'pt' ? get_post_meta( $pid, 'titulo_' . $lang, true ) : '';
            $t    = ! empty( $t_cus ) ? $t_cus : get_the_title();
            $link = tiete_url_com_lang( get_permalink(), $lang );
            ?>
            <a class="loja-vitrine-item" href="<?php echo esc_url( $link ); ?>" data-projeto-id="<?php echo esc_attr( $pid ); ?>" aria-label="<?php echo esc_attr( $t ); ?>">
                <?php the_post_thumbnail( 'large' ); ?>
            </a>
        <?php endwhile; endif; ?>
    </div>
</main>
<?php get_sidebar(); ?>
<?php get_footer(); ?>
