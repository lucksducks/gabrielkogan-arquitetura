<?php
$lang = tiete_get_lang();
$url_base = home_url('/');
$textos = tiete_get_dicionario($lang);
$idiomas_site = array(
    'pt' => 'PT',
    'en' => 'EN',
    'ja' => 'JP',
);
$cat_slug = isset( $_GET['categoria'] ) ? sanitize_key( $_GET['categoria'] ) : '';

$todos_projetos = new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true) );
?>

<aside class="barra-fixa">
    <div class="navegacao-topo">
        <?php $link_home_logo = tiete_url_com_lang( $url_base, $lang ); ?>
        <a href="<?php echo esc_url( $link_home_logo ); ?>" class="bloco-texto-logo marca-topo-direito btn-home-ajax">
            <span class="nome-principal">GABRIEL KOGAN</span>
            <span class="subtitulo-arquitetura"><?php echo esc_html( $textos['arq_subtit'] ); ?></span>
        </a>

        <nav class="filtros-categoria">
            <ul>
                <?php foreach ( $textos['filtros'] as $slug => $nome ) :
                    $classe_ativo = ( $cat_slug === $slug ) ? 'filtro-ativo' : '';
                    $link_filtro  = add_query_arg( 'categoria', $slug, $url_base );
                    $link_filtro = tiete_url_com_lang( $link_filtro, $lang );
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $link_filtro ); ?>" class="<?php echo esc_attr( $classe_ativo ); ?>" data-slug="<?php echo esc_attr( $slug ); ?>">
                            <?php echo esc_html( $nome ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
                <li><a href="https://tiete178lab.com" target="_blank" rel="noopener noreferrer" class="link-externo">TIETÊ178</a></li>
                <?php foreach ( $idiomas_site as $codigo_idioma => $rotulo_idioma ) :
                    $link_idioma = tiete_url_com_lang( remove_query_arg( 'lang' ), $codigo_idioma );
                    ?>
                    <li>
                        <a href="<?php echo esc_url( $link_idioma ); ?>" class="btn-idioma" style="color:<?php echo $lang === $codigo_idioma ? '#7b7b7b' : '#b5b4af'; ?>;font-weight:<?php echo $lang === $codigo_idioma ? 'bold' : 'normal'; ?>;">
                            <?php echo esc_html( $rotulo_idioma ); ?>
                        </a>
                    </li>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>

    <div class="container-dinamico-lateral">
        <?php if ( is_single() ) :
            $titulo_custom   = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'titulo_' . $lang, true ) : '';
            $titulo_exibicao = ! empty( $titulo_custom ) ? $titulo_custom : get_the_title();
            $autoria_pt      = get_post_meta( get_the_ID(), 'autoria_pt', true );
            $autoria_traduzida = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'autoria_' . $lang, true ) : '';
            $autoria_exibicao = ! empty( $autoria_traduzida ) ? $autoria_traduzida : $autoria_pt;
        ?>
            <div class="ficha-tecnica">
                <h1 class="titulo-projeto-destaque"><?php echo esc_html( $titulo_exibicao ); ?></h1>
                <?php if ( ! empty( $autoria_exibicao ) ) : ?>
                    <div class="autoria-projeto"><?php echo nl2br( esc_html( $autoria_exibicao ) ); ?></div>
                <?php endif; ?>
                <div class="texto-descricao-lateral">
                    <div class="texto-descricao-interno">
                        <?php
                        $texto_traduzido = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'texto_' . $lang, true ) : '';
                        if ( ! empty( $texto_traduzido ) ) {
                            echo wpautop( $texto_traduzido );
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
                            $link_projeto = tiete_url_com_lang( $link_projeto, $lang );
                            $tit_lista_traduzido = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'titulo_' . $lang, true ) : '';
                            $tit_lista    = ! empty( $tit_lista_traduzido ) ? $tit_lista_traduzido : get_the_title();
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
