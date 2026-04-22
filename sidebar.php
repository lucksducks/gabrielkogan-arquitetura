<?php
$lang = ( isset( $_GET['lang'] ) && $_GET['lang'] === 'en' ) ? 'en' : 'pt';
$url_base = home_url('/');
$textos = tiete_get_dicionario($lang);
$link_toggle_lang = ( $lang === 'en' ) ? remove_query_arg('lang') : add_query_arg('lang', 'en');
$cat_slug = isset( $_GET['categoria'] ) ? sanitize_key( $_GET['categoria'] ) : '';

$todos_projetos = new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true) );
?>

<aside class="barra-fixa">
    <div class="navegacao-topo">
        <?php $link_home_logo = ( $lang === 'en' ) ? add_query_arg( 'lang', 'en', $url_base ) : $url_base; ?>
        <a href="<?php echo esc_url( $link_home_logo ); ?>" class="bloco-texto-logo marca-topo-direito btn-home-ajax">
            <span class="nome-principal">GABRIEL KOGAN</span>
            <span class="subtitulo-arquitetura"><?php echo esc_html( $textos['arq_subtit'] ); ?></span>
        </a>

        <nav class="filtros-categoria">
            <ul>
                <?php foreach ( $textos['filtros'] as $slug => $nome ) :
                    $classe_ativo = ( $cat_slug === $slug ) ? 'filtro-ativo' : '';
                    $link_filtro  = add_query_arg( 'categoria', $slug, $url_base );
                    if ( $lang === 'en' ) $link_filtro = add_query_arg( 'lang', 'en', $link_filtro );
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $link_filtro ); ?>" class="<?php echo esc_attr( $classe_ativo ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
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
                            echo apply_filters('the_content', preg_replace('/<img\b[^>]*>/i', '', get_the_content()));
                        }
                        ?>
                    </div>
                </div>
            </div>
        <?php else : ?>
            <nav class="menu-projetos">
                <ul id="listaProjetos">
                    <?php
                    $query_lista = ( $cat_slug ) ? new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true, 'category_name' => $cat_slug) ) : $todos_projetos;
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