<?php
// --- ÁLBUM DA SEMANA ---
$album_q = new WP_Query(['post_type' => 'album-semana', 'posts_per_page' => 1, 'orderby' => 'date', 'order' => 'DESC', 'no_found_rows' => true]);
$album_id = null; $album_titulo = ''; $album_artista = ''; $album_ano = ''; $album_genero = ''; $album_cover = ''; $album_audio = ''; $album_review = ''; $album_faixa = ''; $album_tracklist_arr = []; $album_streaming_arr = [];

if ( $album_q->have_posts() ) {
    $album_q->the_post();
    $album_id = get_the_ID(); $album_titulo = get_the_title();
    $album_artista = get_post_meta( $album_id, 'album_artista', true );
    $album_ano = get_post_meta( $album_id, 'album_ano', true );
    $album_genero = get_post_meta( $album_id, 'album_genero', true );
    $album_cover = get_the_post_thumbnail_url( $album_id, 'large' ) ?: get_post_meta( $album_id, 'spotify_cover_url', true ) ?: '';
    $album_audio = get_post_meta( $album_id, 'album_audio_url', true );
    $album_review = apply_filters( 'the_content', get_the_content() );
    $album_faixa = get_post_meta( $album_id, 'album_faixa_destaque', true );

    $tl_raw = get_post_meta( $album_id, 'album_tracklist', true );
    if ( $tl_raw ) $album_tracklist_arr = array_values( array_filter( array_map( 'trim', explode( "\n", $tl_raw ) ) ) );

    $sl_raw = get_post_meta( $album_id, 'album_streaming_links', true );
    if ( $sl_raw ) {
        foreach ( explode( "\n", $sl_raw ) as $linha ) {
            $p = explode( '|', trim( $linha ), 2 );
            if ( count( $p ) === 2 && ! empty( $p[1] ) ) $album_streaming_arr[] = [ 'name' => $p[0], 'url' => $p[1] ];
        }
    }
    wp_reset_postdata();
}
?>
<section id="secaoAlbum" class="secao-album-home">
    <?php if ( $album_id ) : ?>
    <div class="album-container">
        <div class="album-capa-wrapper">
            <div class="album-capa-disco" id="albumDisc">
                <?php if ( $album_cover ) : ?><img src="<?php echo esc_url( $album_cover ); ?>" crossorigin="anonymous" alt="<?php echo esc_attr( $album_titulo ); ?>"><?php endif; ?>
            </div>
        </div>
        <div class="album-info">
            <div class="album-cabecalho">
                <span class="album-artista"><?php echo esc_html( $album_artista ); ?></span>
                <h2 class="album-titulo"><?php echo esc_html( $album_titulo ); ?></h2>
                <span class="album-meta"><?php echo esc_html( $album_ano ); ?><?php if ( $album_genero ) echo ' · ' . esc_html( $album_genero ); ?></span>
            </div>

            <div class="album-player" id="albumPlayer" data-src="<?php echo esc_url( $album_audio ); ?>">
                <audio id="albumAudio" preload="none"></audio>
                <button class="album-play-btn" id="albumPlayBtn" aria-label="<?php echo $album_audio ? 'Tocar' : 'Preview não disponível'; ?>" <?php echo !$album_audio ? 'disabled' : ''; ?>><svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><polygon points="2,1 9,5 2,9"/></svg></button>
                <div class="album-progresso-track" id="albumProgressTrack"><div class="album-progresso-fill" id="albumProgressFill"></div></div>
                <span class="album-tempo" id="albumTempo"><?php echo $album_audio ? '0:00' : '—'; ?></span>
            </div>

            <?php if ( $album_review ) : ?><div class="album-review" id="albumReview"><?php echo $album_review; ?></div><?php endif; ?>

            <?php if ( $album_tracklist_arr ) : ?>
            <details class="album-tracklist" id="albumTracklist">
                <summary>TRACKLIST</summary>
                <ol>
                    <?php
                    $faixa_norm = $album_faixa ? preg_replace('/[^a-z0-9]/', '', strtolower($album_faixa)) : '';
                    foreach ( $album_tracklist_arr as $faixa_item ) :
                        $item_norm = preg_replace('/[^a-z0-9]/', '', strtolower($faixa_item));
                        $destaque  = $faixa_norm && $item_norm && (str_contains($item_norm, $faixa_norm) || str_contains($faixa_norm, $item_norm));
                    ?>
                        <li<?php if ($destaque) echo ' class="faixa-destaque"'; ?>><?php echo esc_html( $faixa_item ); ?></li>
                    <?php endforeach; ?>
                </ol>
            </details>
            <?php endif; ?>

            <?php if ( $album_streaming_arr ) : ?>
            <div class="album-streaming" id="albumStreaming">
                <?php foreach ( $album_streaming_arr as $link ) : ?>
                    <a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['name'] ); ?></a>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <button class="album-arquivo-btn" id="albumArquivoBtn">VER ARQUIVO &rarr;</button>
        </div>
    </div>

    <div class="album-arquivo-overlay" id="albumArquivoOverlay" aria-hidden="true">
        <button class="album-arquivo-fechar" id="albumArquivoFechar" aria-label="Fechar">&times;</button>
        <div class="album-arquivo-grid" id="albumArquivoGrid"></div>
    </div>
    <?php else : ?>
    <div class="album-container album-vazio"><p>Nenhum álbum cadastrado ainda.</p></div>
    <?php endif; ?>
</section>