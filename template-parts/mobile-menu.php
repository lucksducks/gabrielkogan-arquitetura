<?php
$mobile_lang = tiete_get_lang();
$mobile_textos = tiete_get_dicionario( $mobile_lang );
$mobile_home = tiete_url_com_lang( home_url( '/' ), $mobile_lang );
$mobile_categoria = isset( $_GET['categoria'] ) ? sanitize_key( wp_unslash( $_GET['categoria'] ) ) : '';
$mobile_categoria = isset( $mobile_textos['filtros'][ $mobile_categoria ] ) ? $mobile_categoria : '';
$mobile_projetos = get_posts( array(
    'post_type' => 'post',
    'posts_per_page' => -1,
    'orderby' => 'title',
    'order' => 'ASC',
) );
$mobile_destaques = tiete_get_projetos_home_mobile_curados();
$mobile_idiomas = array( 'pt' => 'PT', 'en' => 'EN', 'ja' => 'JP' );
?>
<button type="button" id="mobileMenuToggle" class="mobile-menu-toggle" aria-controls="mobileMenu" aria-expanded="false" aria-label="<?php echo esc_attr( $mobile_textos['menu_abrir'] ); ?>">
    <span aria-hidden="true"></span>
</button>
<div id="mobileMenuBackdrop" class="mobile-menu-backdrop" aria-hidden="true"></div>
<nav id="mobileMenu" class="mobile-menu" aria-label="<?php echo esc_attr( $mobile_textos['menu_titulo'] ); ?>" data-category="<?php echo esc_attr( $mobile_categoria ); ?>" inert>
    <div class="mobile-menu-top">
        <a href="<?php echo esc_url( $mobile_home ); ?>" class="mobile-menu-brand mobile-menu-home">GABRIEL KOGAN<br><?php echo esc_html( $mobile_textos['arq_subtit'] ); ?></a>
        <button type="button" id="mobileMenuClose" class="mobile-menu-close" aria-label="<?php echo esc_attr( $mobile_textos['menu_fechar'] ); ?>">
            <svg viewBox="0 0 32 32" aria-hidden="true" focusable="false">
                <path d="M8 8 24 24M24 8 8 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="butt"/>
            </svg>
        </button>
    </div>
    <div class="mobile-menu-content">
        <div class="mobile-menu-filters" role="group" aria-label="<?php echo esc_attr( $mobile_textos['categorias'] ); ?>">
            <button type="button" data-mobile-category="" aria-pressed="<?php echo $mobile_categoria === '' ? 'true' : 'false'; ?>"><?php echo esc_html( $mobile_textos['todos'] ); ?></button>
            <?php foreach ( $mobile_textos['filtros'] as $slug => $nome ) : ?>
                <button type="button" data-mobile-category="<?php echo esc_attr( $slug ); ?>" aria-pressed="<?php echo $mobile_categoria === $slug ? 'true' : 'false'; ?>"><?php echo esc_html( $nome ); ?></button>
            <?php endforeach; ?>
        </div>

        <div class="mobile-menu-projects">
            <div class="mobile-menu-featured">
                <?php foreach ( $mobile_destaques as $id ) :
                    $titulo = $mobile_lang !== 'pt' ? get_post_meta( $id, 'titulo_' . $mobile_lang, true ) : '';
                    $titulo = $titulo ?: get_the_title( $id );
                    $categorias = wp_get_post_categories( $id, array( 'fields' => 'slugs' ) );
                    $url = tiete_url_com_lang( get_permalink( $id ), $mobile_lang );
                    ?>
                    <a class="mobile-menu-project-link" href="<?php echo esc_url( $url ); ?>" data-mobile-categories="<?php echo esc_attr( implode( ' ', $categorias ) ); ?>"<?php echo is_single() && get_queried_object_id() === $id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $titulo ); ?></a>
                <?php endforeach; ?>
            </div>

            <button type="button" class="mobile-menu-show-all" aria-controls="mobileMenuAll" aria-expanded="false" data-label-all="<?php echo esc_attr( $mobile_textos['ver_todos'] ); ?>" data-label-featured="<?php echo esc_attr( $mobile_textos['ver_selecao'] ); ?>"><?php echo esc_html( $mobile_textos['ver_todos'] ); ?></button>

            <div id="mobileMenuAll" class="mobile-menu-all" hidden>
                <?php foreach ( $mobile_projetos as $projeto ) :
                    $id = $projeto->ID;
                    $titulo = $mobile_lang !== 'pt' ? get_post_meta( $id, 'titulo_' . $mobile_lang, true ) : '';
                    $titulo = $titulo ?: get_the_title( $id );
                    $categorias = wp_get_post_categories( $id, array( 'fields' => 'slugs' ) );
                    $url = tiete_url_com_lang( get_permalink( $id ), $mobile_lang );
                    ?>
                    <a class="mobile-menu-project-link" href="<?php echo esc_url( $url ); ?>" data-mobile-categories="<?php echo esc_attr( implode( ' ', $categorias ) ); ?>"<?php echo is_single() && get_queried_object_id() === $id ? ' aria-current="page"' : ''; ?>><?php echo esc_html( $titulo ); ?></a>
                <?php endforeach; ?>
            </div>
            <p class="mobile-menu-empty" hidden><?php echo esc_html( $mobile_textos['sem_projetos'] ); ?></p>
        </div>
    </div>

    <div class="mobile-menu-footer">
        <div class="mobile-menu-footer-links">
            <a href="<?php echo esc_url( $mobile_home . '#secaoSobre' ); ?>" class="mobile-menu-about mobile-menu-home"><?php echo esc_html( $mobile_textos['pratica_tit'] ); ?></a>
            <a href="https://tiete178lab.com" target="_blank" rel="noopener noreferrer">TIETÊ178 LAB</a>
        </div>
        <div class="mobile-menu-languages" aria-label="<?php echo esc_attr( $mobile_textos['idiomas'] ); ?>">
            <?php foreach ( $mobile_idiomas as $codigo => $rotulo ) :
                $url = tiete_url_com_lang( remove_query_arg( 'lang' ), $codigo );
                ?>
                <a href="<?php echo esc_url( $url ); ?>" lang="<?php echo esc_attr( $codigo ); ?>"<?php echo $mobile_lang === $codigo ? ' aria-current="true"' : ''; ?>><?php echo esc_html( $rotulo ); ?></a>
            <?php endforeach; ?>
        </div>
    </div>
</nav>
