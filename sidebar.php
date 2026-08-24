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
        <?php if ( is_singular( 'produto' ) ) :
            tiete_render_ficha_produto( get_the_ID(), $lang, $textos );
        elseif ( is_post_type_archive( 'produto' ) || is_tax( 'tipo_produto' ) ) :
            $tipo_slug_atual = is_tax( 'tipo_produto' ) ? get_queried_object()->slug : '';
            tiete_render_lista_produtos( $lang, $tipo_slug_atual );
        elseif ( is_single() ) :
            $post_id         = get_the_ID();
            $is_curso        = has_category( 'cursos', $post_id );
            $titulo_custom   = $lang !== 'pt' ? get_post_meta( $post_id, 'titulo_' . $lang, true ) : '';
            $titulo_exibicao = ! empty( $titulo_custom ) ? $titulo_custom : get_the_title();

            if ( $is_curso ) :
                $professor       = get_post_meta( $post_id, 'curso_professor', true );
                $duracao         = get_post_meta( $post_id, 'curso_duracao',   true );
                $preco           = get_post_meta( $post_id, 'curso_preco',     true );
                $vagas           = get_post_meta( $post_id, 'curso_vagas',     true );
                $cta_url         = get_post_meta( $post_id, 'curso_cta_url',   true );
                $shopify_produto = get_post_meta( $post_id, 'curso_shopify_produto', true );
                $shopify_pronto  = defined('SHOPIFY_STORE_DOMAIN') && SHOPIFY_STORE_DOMAIN && defined('SHOPIFY_STOREFRONT_TOKEN') && SHOPIFY_STOREFRONT_TOKEN;

                $timestamp      = strtotime( get_post_meta( $post_id, 'curso_data_hora', true ) );
                $data_formatada = $timestamp ? ( date( 'd/m/Y', $timestamp ) . ' · ' . date( 'H\hi', $timestamp ) ) : '';
            else :
                $autoria_pt        = get_post_meta( $post_id, 'autoria_pt', true );
                $autoria_traduzida = $lang !== 'pt' ? get_post_meta( $post_id, 'autoria_' . $lang, true ) : '';
                $autoria_exibicao  = ! empty( $autoria_traduzida ) ? $autoria_traduzida : $autoria_pt;
            endif;
        ?>
            <div class="ficha-tecnica<?php echo $is_curso ? ' ficha-tecnica--curso' : ''; ?>">
                <h1 class="titulo-projeto-destaque"><?php echo esc_html( $titulo_exibicao ); ?></h1>

                <?php if ( $is_curso ) : ?>

                    <?php if ( $professor || $data_formatada || $duracao ) : ?>
                        <div class="autoria-projeto curso-meta-linha">
                            <?php if ( $professor ) : ?><span><?php echo esc_html( $textos['professor'] ); ?>: <?php echo esc_html( $professor ); ?></span><?php endif; ?>
                            <?php if ( $data_formatada ) : ?><span><?php echo esc_html( $data_formatada ); ?></span><?php endif; ?>
                            <?php if ( $duracao ) : ?><span><?php echo esc_html( $duracao ); ?></span><?php endif; ?>
                        </div>
                    <?php endif; ?>

                <?php elseif ( ! empty( $autoria_exibicao ) ) : ?>
                    <div class="autoria-projeto"><?php echo nl2br( esc_html( $autoria_exibicao ) ); ?></div>
                <?php endif; ?>

                <div class="texto-descricao-lateral">
                    <div class="texto-descricao-interno">
                        <?php
                        $texto_traduzido = $lang !== 'pt' ? get_post_meta( $post_id, 'texto_' . $lang, true ) : '';
                        if ( ! empty( $texto_traduzido ) ) {
                            echo wpautop( $texto_traduzido );
                        } else {
                            echo apply_filters('the_content', preg_replace('/<img\b[^>]*>/i', '', get_the_content()));
                        }
                        ?>
                    </div>
                </div>

                <?php if ( $is_curso ) : ?>
                    <div class="curso-compra-box">
                        <div class="curso-compra-info">
                            <?php if ( $preco || $vagas !== '' ) : ?>
                                <div class="curso-preco-linha">
                                    <?php if ( $preco ) : ?>
                                        <span class="curso-preco"><?php echo esc_html( $preco ); ?></span>
                                    <?php endif; ?>
                                    <?php if ( $vagas !== '' ) : ?>
                                        <span class="curso-vagas"><?php echo esc_html( $vagas ); ?> <?php echo esc_html( $textos['vagas_label'] ); ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                            <?php if ( $shopify_pronto && $shopify_produto && defined('SHOPIFY_CUPOM_ESTUDANTE') && SHOPIFY_CUPOM_ESTUDANTE ) : ?>
                                <label class="curso-check-estudante">
                                    <input type="checkbox" class="curso-input-estudante">
                                    <?php echo esc_html( $textos['meia_estudante'] ); ?>
                                </label>
                            <?php endif; ?>
                        </div>
                        <?php if ( $shopify_pronto && $shopify_produto ) : ?>
                            <button type="button" class="btn-inscrever-se btn-comprar-shopify" data-produto="<?php echo esc_attr( $shopify_produto ); ?>"><?php echo esc_html( $textos['inscrever_se'] ); ?></button>
                        <?php elseif ( $cta_url ) : ?>
                            <a href="<?php echo esc_url( $cta_url ); ?>" class="btn-inscrever-se" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $textos['inscrever_se'] ); ?></a>
                        <?php else : ?>
                            <span class="btn-inscrever-se btn-inscrever-se--em-breve"><?php echo esc_html( $textos['inscrever_se'] ); ?></span>
                        <?php endif; ?>
                        <span class="curso-compra-erro" aria-live="polite"></span>
                    </div>
                <?php endif; ?>
            </div>
        <?php else : ?>
            <?php // Sem filtro de categoria = lista curada da home: marca p/ "não rolar / caber no frame". ?>
            <nav class="menu-projetos<?php echo ! $cat_slug ? ' menu-projetos--home-fit' : ''; ?>">
                <ul id="listaProjetos">
                    <?php
                    // Curadoria da home: se houver menu 'projetos_home', usa-o (ordem arrastada);
                    // senão, fallback = todos os projetos (alfabético). O filtro de categoria
                    // continua listando todos daquela categoria, como antes.
                    $curados     = function_exists( 'tiete_get_projetos_home_curados' ) ? tiete_get_projetos_home_curados() : array();
                    $reset_lista = false;
                    if ( $cat_slug ) {
                        $query_lista = new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC', 'no_found_rows' => true, 'category_name' => $cat_slug) );
                        $reset_lista = true;
                    } elseif ( ! empty( $curados ) ) {
                        $q_curados = new WP_Query( array('post_type' => 'post', 'posts_per_page' => -1, 'post__in' => $curados, 'orderby' => 'post__in', 'no_found_rows' => true, 'ignore_sticky_posts' => true) );
                        if ( $q_curados->have_posts() ) { $query_lista = $q_curados; $reset_lista = true; }
                        else { $query_lista = $todos_projetos; } // curados sem publicados → fallback
                    } else {
                        $query_lista = $todos_projetos;
                    }
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
                    if ($reset_lista) wp_reset_postdata();

                    // Produtos aninhados na aba temática: Pesquisa→livros, Design→objetos, Cursos→cursos.
                    $mapa_categoria_tipo = array( 'pesquisa' => 'livro', 'design' => 'objeto', 'cursos' => 'curso' );
                    if ( $cat_slug && isset( $mapa_categoria_tipo[ $cat_slug ] ) ) {
                        $q_prod = new WP_Query( array(
                            'post_type'      => 'produto',
                            'posts_per_page' => -1,
                            'orderby'        => 'title',
                            'order'          => 'ASC',
                            'no_found_rows'  => true,
                            'tax_query'      => array( array(
                                'taxonomy' => 'tipo_produto',
                                'field'    => 'slug',
                                'terms'    => $mapa_categoria_tipo[ $cat_slug ],
                            ) ),
                        ) );
                        while ( $q_prod->have_posts() ) : $q_prod->the_post();
                            $link_prod = add_query_arg( 'categoria', $cat_slug, get_permalink() );
                            $link_prod = tiete_url_com_lang( $link_prod, $lang );
                            $tit_prod_trad = $lang !== 'pt' ? get_post_meta( get_the_ID(), 'titulo_' . $lang, true ) : '';
                            $tit_prod      = ! empty( $tit_prod_trad ) ? $tit_prod_trad : get_the_title();
                            ?>
                            <li class="item-projeto item-produto" data-projeto-id="<?php echo get_the_ID(); ?>">
                                <a href="<?php echo esc_url( $link_prod ); ?>"><?php echo esc_html( $tit_prod ); ?></a>
                            </li>
                            <?php
                        endwhile;
                        wp_reset_postdata();
                    }
                    ?>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</aside>
