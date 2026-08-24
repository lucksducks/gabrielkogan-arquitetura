</div>

<?php
// ===== CARRINHO (drawer) — visual provisório; design dedicado depois =====
$lang_c   = tiete_get_lang();
$textos_c = tiete_get_dicionario( $lang_c );
?>
<button type="button" id="carrinhoToggle" class="carrinho-toggle" style="display:none;" aria-label="<?php echo esc_attr( $textos_c['carrinho'] ); ?>">
    <svg class="carrinho-icone" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
        <circle cx="9" cy="21" r="1"></circle>
        <circle cx="20" cy="21" r="1"></circle>
        <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
    </svg>
    <span id="carrinhoContador" class="carrinho-contador">0</span>
</button>
<div id="carrinhoOverlay" class="carrinho-overlay"></div>
<aside id="carrinhoDrawer" class="carrinho-drawer" aria-label="<?php echo esc_attr( $textos_c['carrinho'] ); ?>">
    <div class="carrinho-topo">
        <h2><?php echo esc_html( $textos_c['seu_carrinho'] ); ?></h2>
        <button type="button" id="carrinhoFechar" class="carrinho-fechar" aria-label="Fechar">✕</button>
    </div>
    <div id="carrinhoItens" class="carrinho-itens"></div>
    <div class="carrinho-rodape">
        <div class="carrinho-subtotal-linha">
            <span><?php echo esc_html( $textos_c['subtotal'] ); ?></span>
            <span id="carrinhoSubtotal">R$ 0,00</span>
        </div>
        <p class="carrinho-frete-nota"><?php echo esc_html( $textos_c['frete_nota'] ); ?></p>
        <button type="button" id="carrinhoCheckout" class="carrinho-checkout" data-url="" disabled><?php echo esc_html( $textos_c['finalizar'] ); ?></button>
    </div>
</aside>

<?php wp_footer(); ?>
</body>
</html>