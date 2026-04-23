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
<section id="secaoAlbum" class="secao-snap secao-album-home">
    <?php if ( $album_id ) : ?>
    <div class="album-grid-wrapper">
        
        <div class="album-top-row">
            
            <div class="album-col-capa">
                <div class="album-capa-disco" id="albumDisc">
                    <?php if ( $album_cover ) : ?>
                        <img src="<?php echo esc_url( $album_cover ); ?>" crossorigin="anonymous" alt="<?php echo esc_attr( $album_titulo ); ?>">
                    <?php endif; ?>
                </div>
            </div>

            <div class="album-col-info">
                
                <div class="album-cabecalho-novo">
                    <span class="album-artista">
                        <?php echo esc_html( $album_artista ); ?><?php if ( $album_ano ) echo ', ' . esc_html( $album_ano ); ?>
                    </span>
                    <h2 class="album-titulo-novo"><?php echo esc_html( $album_titulo ); ?></h2>
                </div>

                <div class="album-player-novo" id="albumPlayer" data-src="<?php echo esc_url( $album_audio ); ?>">
                    <audio id="albumAudio" preload="none"></audio>
                    <button class="album-play-btn" id="albumPlayBtn" aria-label="<?php echo $album_audio ? 'Tocar' : 'Preview não disponível'; ?>" <?php echo !$album_audio ? 'disabled' : ''; ?>>
                        <svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><polygon points="2,1 9,5 2,9"/></svg>
                    </button>
                    <div class="album-progresso-track" id="albumProgressTrack">
                        <div class="album-progresso-fill" id="albumProgressFill"></div>
                    </div>
                    <span class="album-tempo" id="albumTempo"><?php echo $album_audio ? '0:00' : '—'; ?></span>
                </div>

                <div class="album-caixa-scroll">
                    
                    <?php if ( !empty($album_faixa) ) : ?>
                        <div class="album-faixa-selecionada">
                            <span class="faixa-nome"><?php echo esc_html( $album_faixa ); ?></span>
                        </div>
                    <?php endif; ?>

                    <?php if ( $album_review ) : ?>
                        <div class="album-review" id="albumReview"><?php echo $album_review; ?></div>
                    <?php endif; ?>

                    <?php if ( $album_streaming_arr ) : ?>
                    <div class="album-streaming" id="albumStreaming">
                        <?php foreach ( $album_streaming_arr as $link ) : ?>
                            <a href="<?php echo esc_url( $link['url'] ); ?>" target="_blank" rel="noopener noreferrer"><?php echo esc_html( $link['name'] ); ?></a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
                
            </div>
        </div>

        <div class="album-bottom-row">
            <button class="slider-btn btn-prev" aria-label="Anterior">&lt;</button>
            <div class="album-slider-arquivos">
                <?php
                if (function_exists('tiete_get_arquivo_leve')) {
                    $arquivos = tiete_get_arquivo_leve(); 
                    if($arquivos): foreach($arquivos as $arq): ?>
                        <div class="album-card-mini" data-id="<?php echo esc_attr($arq['id']); ?>" title="<?php echo esc_attr($arq['titulo'] . ' - ' . $arq['artista']); ?>">
                            <?php if($arq['thumb_url']): ?>
                                <img src="<?php echo esc_url($arq['thumb_url']); ?>" alt="Capa">
                            <?php else: ?>
                                <div style="width:100%; height:100%; background:#e0e0e0;"></div>
                            <?php endif; ?>
                        </div>
                    <?php endforeach; endif; 
                } ?>
            </div>
            <button class="slider-btn btn-next" aria-label="Próximo">&gt;</button>
        </div>

    </div>
    <?php else : ?>
    <div class="album-container album-vazio"><p>Nenhum álbum cadastrado ainda.</p></div>
    <?php endif; ?>
</section>