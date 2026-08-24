<?php
// =========================================================================
// 🛒 SHOPIFY — CONSULTA SERVER-SIDE DE PREÇO/ESTOQUE (STOREFRONT API)
// =========================================================================
// Modelo híbrido: o WP guarda só o `shopify_handle`; preço, disponibilidade
// e id da variante vêm AO VIVO do Shopify, com cache em transient (~10 min)
// para não bater na API a cada visita. O checkout em si é feito no JS
// (Cart API) — aqui é só leitura para renderizar a ficha.
// =========================================================================

if ( ! defined( 'ABSPATH' ) ) exit;

if ( ! defined( 'SHOPIFY_API_VERSION' ) ) {
    define( 'SHOPIFY_API_VERSION', '2026-07' );
}

/** True se as constantes do Shopify estão configuradas. */
function tiete_shopify_pronto() {
    return defined( 'SHOPIFY_STORE_DOMAIN' ) && SHOPIFY_STORE_DOMAIN
        && defined( 'SHOPIFY_STOREFRONT_TOKEN' ) && SHOPIFY_STOREFRONT_TOKEN;
}

/**
 * Chama a Storefront API (GraphQL) no servidor.
 * @return array|null Corpo decodificado ou null em erro.
 */
function tiete_shopify_graphql( $query, $variables = array() ) {
    if ( ! tiete_shopify_pronto() ) return null;

    $url = 'https://' . SHOPIFY_STORE_DOMAIN . '/api/' . SHOPIFY_API_VERSION . '/graphql.json';
    $resp = wp_remote_post( $url, array(
        'timeout' => 8,
        'headers' => array(
            'Content-Type'                      => 'application/json',
            'X-Shopify-Storefront-Access-Token' => SHOPIFY_STOREFRONT_TOKEN,
        ),
        'body' => wp_json_encode( array( 'query' => $query, 'variables' => $variables ) ),
    ) );

    if ( is_wp_error( $resp ) || wp_remote_retrieve_response_code( $resp ) !== 200 ) return null;
    $json = json_decode( wp_remote_retrieve_body( $resp ), true );
    return is_array( $json ) ? $json : null;
}

/**
 * Preço/estoque de um produto pelo handle, com cache.
 * @return array {
 *   ok, disponivel, preco_amount (float), preco_moeda, preco_formatado,
 *   variant_id, titulo
 * } — ['ok' => false] se não achou / API indisponível.
 */
function tiete_shopify_info_produto( $handle, $forcar = false ) {
    $handle = sanitize_title( $handle );
    if ( ! $handle || ! tiete_shopify_pronto() ) return array( 'ok' => false );

    $cache_key = 'tiete_shp_' . md5( $handle );
    if ( ! $forcar ) {
        $cache = get_transient( $cache_key );
        if ( $cache !== false ) return $cache;
    }

    $query = 'query($handle: String!) {
        product(handle: $handle) {
            title
            variant: selectedOrFirstAvailableVariant {
                id
                availableForSale
                price { amount currencyCode }
            }
        }
    }';

    $json = tiete_shopify_graphql( $query, array( 'handle' => $handle ) );
    $p    = $json['data']['product'] ?? null;

    if ( ! $p || empty( $p['variant'] ) ) {
        $out = array( 'ok' => false );
        set_transient( $cache_key, $out, 5 * MINUTE_IN_SECONDS ); // cache curto p/ erros
        return $out;
    }

    $v      = $p['variant'];
    $amount = isset( $v['price']['amount'] ) ? (float) $v['price']['amount'] : 0;
    $moeda  = $v['price']['currencyCode'] ?? 'BRL';

    $out = array(
        'ok'              => true,
        'disponivel'      => ! empty( $v['availableForSale'] ),
        'preco_amount'    => $amount,
        'preco_moeda'     => $moeda,
        'preco_formatado' => tiete_shopify_formatar_preco( $amount, $moeda ),
        'variant_id'      => $v['id'] ?? '',
        'titulo'          => $p['title'] ?? '',
    );

    set_transient( $cache_key, $out, 10 * MINUTE_IN_SECONDS );
    return $out;
}

/** Formata preço conforme a moeda (BRL em pt-BR, senão símbolo + valor). */
function tiete_shopify_formatar_preco( $amount, $moeda = 'BRL' ) {
    if ( $moeda === 'BRL' ) {
        return 'R$ ' . number_format( $amount, 2, ',', '.' );
    }
    $simbolos = array( 'USD' => 'US$ ', 'EUR' => '€ ', 'JPY' => '¥ ', 'GBP' => '£ ' );
    $prefixo  = $simbolos[ $moeda ] ?? ( $moeda . ' ' );
    $casas    = $moeda === 'JPY' ? 0 : 2;
    return $prefixo . number_format( $amount, $casas, '.', ',' );
}

/** Limpa o cache de preço de um handle (ex.: após editar o produto). */
function tiete_shopify_limpar_cache( $handle ) {
    delete_transient( 'tiete_shp_' . md5( sanitize_title( $handle ) ) );
}

// Ao salvar um produto, invalida o cache do handle para refletir mudanças logo.
add_action( 'save_post_produto', function ( $post_id ) {
    $handle = get_post_meta( $post_id, 'shopify_handle', true );
    if ( $handle ) tiete_shopify_limpar_cache( $handle );
}, 20 );
