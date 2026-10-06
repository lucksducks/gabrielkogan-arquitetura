document.addEventListener('DOMContentLoaded', () => {

    // =========================================================================
    // REFERÊNCIAS DOM E ESTADO GLOBAL
    // =========================================================================
    const area      = document.getElementById('mainContent');
    const lateral   = document.querySelector('.container-dinamico-lateral');
    const barraZoom = document.getElementById('barraZoom');
    const mobileLayout = window.matchMedia('(max-width: 1024px)');
    const ehMobile = () => mobileLayout.matches;

    const menuMobile = document.getElementById('mobileMenu');
    const botaoMenuMobile = document.getElementById('mobileMenuToggle');
    const botaoFecharMenu = document.getElementById('mobileMenuClose');
    const fundoMenuMobile = document.getElementById('mobileMenuBackdrop');
    const barraLateral = document.querySelector('.barra-fixa');
    const logoEasterEgg = document.getElementById('logoEasterEgg');
    const metaCorTemaOriginal = document.querySelector('meta[name="theme-color"]');
    const corTemaOriginal = metaCorTemaOriginal?.content;
    let metaCorTemaMenu = metaCorTemaOriginal;

    function definirMenuMobile(aberto) {
        if (!menuMobile || !botaoMenuMobile) return;
        if (aberto) {
            if (!metaCorTemaMenu) {
                metaCorTemaMenu = document.createElement('meta');
                metaCorTemaMenu.name = 'theme-color';
                document.head.appendChild(metaCorTemaMenu);
            }
            metaCorTemaMenu.content = getComputedStyle(document.documentElement).getPropertyValue('--cor-bg-principal').trim();
        } else if (metaCorTemaOriginal) {
            metaCorTemaOriginal.content = corTemaOriginal;
        } else {
            metaCorTemaMenu?.remove();
            metaCorTemaMenu = null;
        }
        document.body.classList.toggle('mobile-menu-open', aberto);
        menuMobile.inert = !aberto;
        botaoMenuMobile.setAttribute('aria-expanded', String(aberto));
        area.inert = aberto;
        if (barraLateral) barraLateral.inert = aberto;
        if (logoEasterEgg) logoEasterEgg.inert = aberto;
        botaoMenuMobile.inert = aberto;
        if (aberto) {
            botaoFecharMenu?.focus();
        } else if (menuMobile.contains(document.activeElement)) {
            botaoMenuMobile.focus();
        }
    }

    function atualizarIndiceMenuMobile() {
        if (!menuMobile) return;
        const categoria = menuMobile.dataset.category || '';
        const expandido = menuMobile.dataset.expanded === 'true';
        const mostrarIndice = !!categoria || expandido;
        const destaques = menuMobile.querySelector('.mobile-menu-featured');
        const indice = menuMobile.querySelector('.mobile-menu-all');
        const botaoIndice = menuMobile.querySelector('.mobile-menu-show-all');
        const vazio = menuMobile.querySelector('.mobile-menu-empty');

        menuMobile.querySelectorAll('[data-mobile-category]').forEach(botao => {
            botao.setAttribute('aria-pressed', String(botao.dataset.mobileCategory === categoria));
        });
        if (destaques) destaques.hidden = mostrarIndice;
        if (indice) indice.hidden = !mostrarIndice;
        if (botaoIndice) {
            botaoIndice.hidden = !!categoria;
            botaoIndice.setAttribute('aria-expanded', String(expandido));
            botaoIndice.textContent = expandido ? botaoIndice.dataset.labelFeatured : botaoIndice.dataset.labelAll;
        }

        let visiveis = 0;
        indice?.querySelectorAll('.mobile-menu-project-link').forEach(link => {
            const categorias = (link.dataset.mobileCategories || '').split(' ');
            link.hidden = !!categoria && !categorias.includes(categoria);
            if (!link.hidden) visiveis++;
        });
        if (vazio) vazio.hidden = !categoria || visiveis > 0;
    }

    atualizarIndiceMenuMobile();

    botaoMenuMobile?.addEventListener('click', () => definirMenuMobile(true));
    botaoFecharMenu?.addEventListener('click', () => definirMenuMobile(false));
    fundoMenuMobile?.addEventListener('click', () => definirMenuMobile(false));
    menuMobile?.addEventListener('click', (e) => {
        const filtro = e.target.closest('[data-mobile-category]');
        if (filtro) {
            menuMobile.dataset.category = filtro.dataset.mobileCategory;
            menuMobile.dataset.expanded = 'false';
            atualizarIndiceMenuMobile();
            menuMobile.querySelector('.mobile-menu-content').scrollTop = 0;
            return;
        }
        if (e.target.closest('.mobile-menu-show-all')) {
            menuMobile.dataset.expanded = String(menuMobile.dataset.expanded !== 'true');
            atualizarIndiceMenuMobile();
            menuMobile.querySelector('.mobile-menu-content').scrollTop = 0;
            return;
        }
        if (e.target.closest('a')) definirMenuMobile(false);
    });
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape' && document.body.classList.contains('mobile-menu-open')) definirMenuMobile(false);
    });

    // Deslizar a partir da borda direita abre o menu; o gesto inverso o fecha.
    let inicioGestoMenu = null;
    document.addEventListener('touchstart', (e) => {
        if (!ehMobile() || e.touches.length !== 1) return;
        const toque = e.touches[0];
        const aberto = document.body.classList.contains('mobile-menu-open');
        if (!aberto && toque.clientX < window.innerWidth - 96) return;
        inicioGestoMenu = { x: toque.clientX, y: toque.clientY, id: toque.identifier, aberto };
    }, { passive: true });
    document.addEventListener('touchend', (e) => {
        if (!inicioGestoMenu || !ehMobile() || e.touches.length) return;
        const toque = Array.from(e.changedTouches).find(t => t.identifier === inicioGestoMenu.id);
        if (!toque) { inicioGestoMenu = null; return; }
        const dx = toque.clientX - inicioGestoMenu.x;
        const dy = toque.clientY - inicioGestoMenu.y;
        if (Math.abs(dx) >= 64 && Math.abs(dx) > Math.abs(dy) * 1.5) {
            if (!inicioGestoMenu.aberto && dx < 0) definirMenuMobile(true);
            if (inicioGestoMenu.aberto && dx > 0) definirMenuMobile(false);
        }
        inicioGestoMenu = null;
    }, { passive: true });
    document.addEventListener('touchcancel', () => { inicioGestoMenu = null; }, { passive: true });

    // ⭐ Garantir que a imagem bg-zoom dinâmica está configurada
    const bgZoomUrl = barraZoom?.dataset.bgZoomUrl;
    if (bgZoomUrl) {
        barraZoom.style.backgroundImage = `url('${bgZoomUrl}')`;
    }

    const globalAudio = new Audio();
    const SVG_PLAY  = '<svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><polygon points="2,1 9,5 2,9"/></svg>';
    const SVG_PAUSE = '<svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><rect x="1.5" y="1" width="2.5" height="8"/><rect x="6" y="1" width="2.5" height="8"/></svg>';

    // Atualiza a barra de progresso sempre que o áudio avançar (se a barra existir na tela)
    globalAudio.addEventListener('timeupdate', () => {
        if (!globalAudio.duration) return;
        const fill = document.getElementById('albumProgressFill');
        const tempo = document.getElementById('albumTempo');
        if (fill && tempo) {
            fill.style.width = (globalAudio.currentTime / globalAudio.duration * 100) + '%';
            const s = Math.floor(globalAudio.currentTime);
            tempo.textContent = `${~~(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        }
    });

    // Reseta a interface quando a música acaba
    globalAudio.addEventListener('ended', () => {
        const playBtn = document.getElementById('albumPlayBtn');
        const fill = document.getElementById('albumProgressFill');
        const tempo = document.getElementById('albumTempo');
        if (playBtn) {
            playBtn.innerHTML = SVG_PLAY;
            playBtn.classList.remove('tocando');
        }
        if (fill) fill.style.width = '0';
        if (tempo) tempo.textContent = '0:00';
    });

    // ⭐ Atualiza a imagem de fundo do barraZoom ao trocar de projeto (AJAX)
    function atualizarBgZoomDinamico() {
        const barraZoom = document.getElementById('barraZoom');
        if (!barraZoom) return;
        const bgZoomUrl = barraZoom.dataset.bgZoomUrl;
        if (bgZoomUrl) {
            barraZoom.style.backgroundImage = `url('${bgZoomUrl}')`;
        } else {
            // Fallback para padrão
            barraZoom.style.backgroundImage = `url('${window.temaConfig?.homeUrl || ''}wp-content/themes/tema-tiete178lab/assets/img/bg-zoom.jpg')`;
        }
    }

    atualizarBgZoomDinamico();

    let permitirsSumico       = false;
    let isCarregando          = false;
    let navegacaoPendente     = null;
    let fichaTecnicaCache     = document.body.classList.contains('single') ? lateral.innerHTML : null;
    let filtroAtivoGlobal     = temaConfig.filtroAtivo;
    let projetoAtivoUrlGlobal = document.body.classList.contains('single') ? window.location.href : null;

    let zoomAtivo       = false;
    let imagemZoomAtual = null;
    let scrollBaseZoom  = 0;
    let barWidthAtual   = 0;
    let transitandoZoom = false;

    let movePreviewRef  = null;
    let timersNavegacao = [];

    // =========================================================================
    // HOME SCROLL SNAP — estado
    // =========================================================================
    let homeSnapIndex    = 0;
    let homeSnapCooldown = false;

    // =========================================================================
    // ALINHAMENTO DINÂMICO — topo da imagem home = topo da lista de projetos
    // =========================================================================
    function sincronizarOffsetImagem() {
        if (!document.body.classList.contains('home') || ehMobile()) return;
        const lista = document.getElementById('listaProjetos');
        if (!lista) return;
        const offset = lista.getBoundingClientRect().top;
        document.documentElement.style.setProperty('--home-img-offset', offset + 'px');
    }

    let resizeTimer = null;
    window.addEventListener('resize', () => {
        clearTimeout(resizeTimer);
        resizeTimer = setTimeout(sincronizarOffsetImagem, 100);
    });

    // Animar o tamanho real do SVG evita ampliar uma camada rasterizada.
    function posicionarLogoEasterEgg() {
        const logo = document.getElementById('logoEasterEgg');
        const site = document.querySelector('.container-principal');
        if (!logo || !site) return;

        const siteRect = site.getBoundingClientRect();
        const imagem = logo.querySelector('.img-logo-pequena');
        const largura = ehMobile() ? 66 : window.innerHeight * 0.13;
        const altura = largura * (imagem?.naturalHeight && imagem?.naturalWidth ? imagem.naturalHeight / imagem.naturalWidth : 8116 / 8461);
        if (!largura || !altura) return;

        // Centro da coluna esquerda, com folga acima e abaixo em janelas baixas.
        const escala = Math.min(siteRect.width * (ehMobile() ? 0.72 : 0.42) / largura, window.innerHeight * 0.72 / altura);
        const centroX = siteRect.left + siteRect.width * (ehMobile() ? 0.5 : 0.3);
        const centroY = window.innerHeight / 2;
        logo.style.setProperty('--logo-easter-left', `${centroX - largura * escala / 2}px`);
        logo.style.setProperty('--logo-easter-top', `${centroY - altura * escala / 2}px`);
        logo.style.setProperty('--logo-easter-width', `${largura * escala}px`);
    }

    window.addEventListener('resize', posicionarLogoEasterEgg);

    let logoAbertaNaEntradaMobile = false;
    if (ehMobile() && window.scrollY < 48 && !window.location.hash && logoEasterEgg) {
        logoEasterEgg.classList.add('logo-inicial-mobile');
        posicionarLogoEasterEgg();
        logoEasterEgg.classList.add('easter-egg-ativo');
        logoEasterEgg.setAttribute('aria-pressed', 'true');
        logoAbertaNaEntradaMobile = true;
        requestAnimationFrame(() => logoEasterEgg.classList.remove('logo-inicial-mobile'));
    }
    window.addEventListener('scroll', () => {
        if (!logoAbertaNaEntradaMobile || window.scrollY < 48) return;
        logoAbertaNaEntradaMobile = false;
        logoEasterEgg.classList.remove('easter-egg-ativo');
        logoEasterEgg.setAttribute('aria-pressed', 'false');
    }, { passive: true });

    document.getElementById('logoEasterEgg')?.addEventListener('keydown', (e) => {
        if (e.key !== 'Enter' && e.key !== ' ') return;
        e.preventDefault();
        e.currentTarget.click();
    });

    function limparTimers() {
        timersNavegacao.forEach(clearTimeout);
        timersNavegacao = [];
        ocultarPreviewProjeto();
        document.querySelectorAll('.clone-titulo-animacao').forEach(el => el.remove());
    }

    // =========================================================================
    // ZOOM DE IMAGEM
    // =========================================================================

    const easeInOutQuint = (t) => t < 0.5 ? 16 * t * t * t * t * t : 1 - Math.pow(-2 * t + 2, 5) / 2;
    let isZoomAnimating = false;

    function snapSairDoZoom(isTransitioning = false) {
        if (!zoomAtivo || isZoomAnimating) return;

        isZoomAnimating = true;
        lenis.stop();

        zoomAtivo = false;
        barWidthAtual = 0;
        document.body.classList.remove('is-zoom-active');
        barraZoom.classList.remove('is-active');
        barraZoom.classList.remove('bg-zoom--panorama', 'bg-zoom--normal', 'bg-zoom--tall');

        area.style.paddingLeft = '0px';
        barraZoom.style.width  = '0px';

        imagemZoomAtual = null;
        isZoomAnimating = false;
        lenis.start();

        if (!isTransitioning) {
            lenis.resize();
        }
    }

    function runZoomAnimation(img, startY, targetY, startPadding, targetPadding, isEntering, allowScroll = false) {
        isZoomAnimating = true;
        if (isEntering) zoomAtivo = true;
        imagemZoomAtual = img;

        if (isEntering) barraZoom.classList.add('is-active');

        lenis.stop();

        const duration    = 700;
        const startTime   = performance.now();
        const scrollAtStart = lenis.scroll;

        function frame(time) {
            const elapsed  = time - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased    = easeInOutQuint(progress);

            const currentPadding = startPadding + (targetPadding - startPadding) * eased;
            area.style.paddingLeft = currentPadding + 'px';
            barraZoom.style.width  = currentPadding + 'px';

            if (!allowScroll) {
                const actualY    = img.getBoundingClientRect().top;
                const expectedY  = startY + (targetY - startY) * eased;
                const diff       = actualY - expectedY;

                area.scrollTop += diff;
                lenis.scrollTo(area.scrollTop, { immediate: true });
            } else {
                lenis.scrollTo(scrollAtStart, { immediate: true });
            }

            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                isZoomAnimating = false;
                if (isEntering) {
                    document.body.classList.add('is-zoom-active');
                    scrollBaseZoom  = lenis.scroll;
                    transitandoZoom = false;
                } else {
                    document.body.classList.remove('is-zoom-active');
                    barraZoom.classList.remove('is-active');
                    barraZoom.classList.remove('bg-zoom--panorama', 'bg-zoom--normal', 'bg-zoom--tall');
                    barWidthAtual   = 0;
                    zoomAtivo       = false;
                    imagemZoomAtual = null;
                    transitandoZoom = false;
                }
                lenis.start();
                lenis.resize();
            }
        }
        requestAnimationFrame(frame);
    }

    function ativarZoom(img) {
        if (!img || isZoomAnimating) return;

        if (zoomAtivo) {
            transitandoZoom = true;
            snapSairDoZoom(true);
        }

        const startY    = img.getBoundingClientRect().top;
        const areaWidth = area.offsetWidth;
        const r         = img.naturalWidth / img.naturalHeight;

        let contentWidth  = window.innerHeight * r;
        let contentHeight = window.innerHeight;

        if (contentWidth > areaWidth) {
            contentWidth  = areaWidth;
            contentHeight = areaWidth / r;
        }

        const barWidth = areaWidth - contentWidth;

        if (barWidth < 0) return;

        if (barWidth < 20) {
            transitandoZoom = false;
            lenis.scrollTo(lenis.scroll + startY, {
                duration: 0.7,
                easing: easeInOutQuint,
            });
            return;
        }

        barWidthAtual = barWidth;
        barraZoom.style.setProperty('--bar-width', barWidth + 'px');

        barraZoom.classList.remove('bg-zoom--panorama', 'bg-zoom--normal', 'bg-zoom--tall');

        if (r > 1.5) {
            barraZoom.classList.add('bg-zoom--panorama');
        } else if (r < 1) {
            barraZoom.classList.add('bg-zoom--tall');
        } else {
            barraZoom.classList.add('bg-zoom--normal');
        }

        const startPadding  = 0;
        const targetPadding = barWidth;
        const targetY       = 0;

        runZoomAnimation(img, startY, targetY, startPadding, targetPadding, true);
    }

    function desativarZoomViaClique() {
        if (!zoomAtivo || isZoomAnimating) return;

        const img = imagemZoomAtual;

        if (!img) {
            snapSairDoZoom();
            return;
        }

        const startY       = img.getBoundingClientRect().top;
        const startPadding = parseFloat(area.style.paddingLeft) || 0;
        const targetPadding = 0;

        area.style.paddingLeft = '0px';
        area.offsetHeight;
        const targetHeight = img.offsetHeight;

        area.style.paddingLeft = startPadding + 'px';
        area.offsetHeight;

        const targetY = (window.innerHeight / 2) - (targetHeight / 2);

        runZoomAnimation(img, startY, targetY, startPadding, targetPadding, false, false);
    }

    // =========================================================================
    // LENIS — SMOOTH SCROLL
    // =========================================================================
    let lenis = new Lenis({
        wrapper:         ehMobile() ? window : area,
        lerp:            0.06,
        wheelMultiplier: 1.2,
        smoothWheel:     !ehMobile(),
    });

    let lenisLateralInstance = null;

    function initLenisLateral() {
        if (lenisLateralInstance) {
            lenisLateralInstance.destroy();
            lenisLateralInstance = null;
        }
        if (ehMobile()) return;
        const wrapper = document.querySelector('.texto-descricao-lateral');
        const content = document.querySelector('.texto-descricao-interno');
        if (wrapper && content) {
            lenisLateralInstance = new Lenis({
                wrapper:         wrapper,
                content:         content,
                lerp:            0.06,
                wheelMultiplier: 1.2,
                smoothWheel:     true,
            });
        }
    }

    function raf(time) {
        lenis.raf(time);
        if (lenisLateralInstance) lenisLateralInstance.raf(time);
        requestAnimationFrame(raf);
    }
    requestAnimationFrame(raf);

    // =========================================================================
    // HOVER PREVIEW
    // =========================================================================
    function ocultarPreviewProjeto() {
        document.querySelector('.area-scroll-thumbs')?.classList.remove('ativo');
        document.querySelectorAll('.prev-hover-img.thumb-ativo').forEach(thumb => thumb.classList.remove('thumb-ativo'));
        document.querySelectorAll('.item-projeto.projeto-em-foco').forEach(item => item.classList.remove('projeto-em-foco'));

        if (movePreviewRef) {
            window.removeEventListener('mousemove', movePreviewRef);
            movePreviewRef = null;
        }
    }

    function mostrarPreviewProjeto(projetoId) {
        const thumbsOverlay = document.querySelector('.area-scroll-thumbs');
        const prevHoverBox = document.getElementById('prevHover');
        const targetThumb = thumbsOverlay?.querySelector(`[data-projeto-id="${projetoId}"]`);
        if (!targetThumb || !prevHoverBox) return null;

        document.querySelectorAll('.prev-hover-img.thumb-ativo').forEach(thumb => thumb.classList.remove('thumb-ativo'));
        targetThumb.classList.add('thumb-ativo');
        thumbsOverlay.classList.add('ativo');
        return prevHoverBox;
    }

    function posicionarPreviewNoGrafico(ponto, box) {
        const grafico = ponto.closest('.yayoi-grafico-area');
        if (!grafico) return;

        const limite = grafico.getBoundingClientRect();
        const origem = ponto.getBoundingClientRect();
        const largura = box.offsetWidth;
        const altura = box.offsetHeight;
        const margem = 8;
        let x = origem.right + 24;
        let y = origem.top - altura - 20;

        if (x + largura > limite.right - margem) x = origem.left - largura - 24;
        if (y < limite.top + margem) y = origem.bottom + 20;

        x = Math.max(limite.left + margem, Math.min(x, limite.right - largura - margem));
        y = Math.max(limite.top + margem, Math.min(y, limite.bottom - altura - margem));
        box.style.transform = `translate3d(${x}px, ${y}px, 0)`;
    }

    function ativarPontoGrafico(ponto) {
        ocultarPreviewProjeto();
        const projetoId = ponto.dataset.projetoId;
        const item = document.querySelector(`.item-projeto[data-projeto-id="${projetoId}"]`);
        item?.classList.add('projeto-em-foco');

        const box = mostrarPreviewProjeto(projetoId);
        if (box) posicionarPreviewNoGrafico(ponto, box);
    }

    document.addEventListener('mouseover', (e) => {
        const ponto = e.target.closest('.yayoi-ponto');
        if (ponto) {
            ativarPontoGrafico(ponto);
            return;
        }

        const item = e.target.closest('.item-projeto');
        if (!item || item.classList.contains('projeto-ativo')) return;

        ocultarPreviewProjeto();
        const box = mostrarPreviewProjeto(item.dataset.projetoId);
        if (!box) return;

        movePreviewRef = (ev) => {
            const largura = box.offsetWidth;
            box.style.transform = `translate3d(${ev.clientX - (largura / 2) - 180}px, ${ev.clientY + 20}px, 0)`;
        };
        window.addEventListener('mousemove', movePreviewRef);
        movePreviewRef(e);
    });

    document.addEventListener('mouseout', (e) => {
        const alvo = e.target.closest('.item-projeto, .yayoi-ponto');
        if (alvo && !alvo.contains(e.relatedTarget)) ocultarPreviewProjeto();
    });

    document.addEventListener('focusin', (e) => {
        const ponto = e.target.closest('.yayoi-ponto');
        if (ponto) ativarPontoGrafico(ponto);
    });

    document.addEventListener('focusout', (e) => {
        if (e.target.closest('.yayoi-ponto')) ocultarPreviewProjeto();
    });

    // =========================================================================
    // NAVEGAÇÃO AJAX
    // =========================================================================
    function liberarNavegacao() {
        isCarregando = false;
        const pendente = navegacaoPendente;
        navegacaoPendente = null;
        if (!pendente) return;

        if (pendente.tipo === 'filtro') {
            toggleLateral(pendente.url, pendente.slug);
        } else {
            carregarPagina(pendente.url, pendente.atualizarHistorico, pendente.apenasLateral);
        }
    }

    function urlSemFiltro(url) {
        const destino = new URL(url, window.location.href);
        destino.searchParams.delete('categoria');
        destino.hash = '';
        return destino.href;
    }

    async function carregarPagina(url, atualizarHistorico = true, apenasLateral = false, elementoClicado = null) {
        if (isCarregando) {
            navegacaoPendente = { tipo: 'pagina', url, atualizarHistorico, apenasLateral };
            return;
        }

        if (ehMobile()) definirMenuMobile(false);
        limparTimers();
        snapSairDoZoom(); 
        isCarregando = true;

        try {
            let rectItemClicado = null;
            let indexClicado    = -1;

            if (elementoClicado && elementoClicado.closest('.menu-projetos')) {
                rectItemClicado = elementoClicado.getBoundingClientRect();
                const linksMenu = Array.from(elementoClicado.closest('.menu-projetos').querySelectorAll('a'));
                indexClicado    = linksMenu.indexOf(elementoClicado);
            }

            const tituloAtual      = lateral.querySelector('.titulo-projeto-destaque');
            let cloneDescida       = null;
            let textoProjetoAtual  = '';
            let rectInicialDescida = null;
            const isIndoParaLista  = apenasLateral;
            const temTituloNaTela  = !!tituloAtual;

            if (isIndoParaLista && temTituloNaTela) {
                textoProjetoAtual  = tituloAtual.innerText.trim();
                rectInicialDescida = tituloAtual.getBoundingClientRect();

                cloneDescida = tituloAtual.cloneNode(true);
                cloneDescida.classList.add('clone-titulo-animacao');

                const descAtual = lateral.querySelector('.texto-descricao-lateral');
                if (descAtual) descAtual.style.opacity = '0';
            }

            if (isIndoParaLista && !temTituloNaTela) {
                const linksSaindo = lateral.querySelectorAll('.item-projeto a');
                if (linksSaindo.length > 0) {
                    linksSaindo.forEach((a, idx) => {
                        a.style.transition = 'transform 0.4s ease, opacity 0.4s ease';
                        timersNavegacao.push(setTimeout(() => {
                            a.style.transform = 'translateY(-15px)';
                            a.style.opacity   = '0';
                        }, idx * 30));
                    });
                    await new Promise(r => timersNavegacao.push(setTimeout(r, 200)));
                }
            }

            const response = await fetch(url);
            const html     = await response.text();
            const parser   = new DOMParser();
            const doc      = parser.parseFromString(html, 'text/html');

            if (!isIndoParaLista) {
                if (ehMobile()) {
                    logoAbertaNaEntradaMobile = false;
                    logoEasterEgg?.classList.remove('easter-egg-ativo');
                    logoEasterEgg?.setAttribute('aria-pressed', 'false');
                }
                const menuReabertoDuranteCarga = document.body.classList.contains('mobile-menu-open');
                area.innerHTML          = doc.querySelector('#mainContent').innerHTML;
                document.body.className = doc.body.className;
                if (menuReabertoDuranteCarga) document.body.classList.add('mobile-menu-open');
                document.title          = doc.title;
                lenis.resize();

                if (document.body.classList.contains('home')) {
                    inicializarAlbum();
                }
            }

            const docNavLista = doc.querySelector('.menu-projetos');
            let linkAlvoIndex = -1;

            if (isIndoParaLista && docNavLista) {
                const docLinks = docNavLista.querySelectorAll('.item-projeto a');
                if (temTituloNaTela) {
                    docLinks.forEach((a, idx) => {
                        if (a.innerText.trim().toUpperCase() === textoProjetoAtual.toUpperCase()) linkAlvoIndex = idx;
                    });
                }
                docLinks.forEach((a, idx) => {
                    if (idx === linkAlvoIndex) {
                        a.style.opacity = '0'; a.style.transform = 'translateY(0)';
                    } else {
                        a.style.transition = 'none'; a.style.transform = 'translateY(15px)'; a.style.opacity = '0';
                    }
                });
            }

            lateral.innerHTML = doc.querySelector('.container-dinamico-lateral').innerHTML;
            initLenisLateral();

            const novoConteudoMenu = doc.querySelector('#mobileMenu .mobile-menu-content');
            const conteudoMenu = menuMobile?.querySelector('.mobile-menu-content');
            if (novoConteudoMenu && conteudoMenu) {
                conteudoMenu.innerHTML = novoConteudoMenu.innerHTML;
                atualizarIndiceMenuMobile();
            }
            const novoRodapeMenu = doc.querySelector('#mobileMenu .mobile-menu-footer');
            const rodapeMenu = menuMobile?.querySelector('.mobile-menu-footer');
            if (novoRodapeMenu && rodapeMenu) rodapeMenu.innerHTML = novoRodapeMenu.innerHTML;

            const tituloDestaque  = lateral.querySelector('.titulo-projeto-destaque');
            const autoriaDestaque = lateral.querySelector('.autoria-projeto');
            const descricao       = lateral.querySelector('.texto-descricao-lateral');

            if (!isIndoParaLista && !ehMobile()) {
                let tituloVoou = false;

                if (tituloDestaque && rectItemClicado) {
                    const rectTituloNovo = tituloDestaque.getBoundingClientRect();
                    const deltaY = rectItemClicado.top  - rectTituloNovo.top;
                    const deltaX = rectItemClicado.left - rectTituloNovo.left;
                    if (Math.abs(deltaY) > 5) {
                        tituloVoou = true;
                        tituloDestaque.style.transition = 'none';
                        tituloDestaque.style.transform  = `translate3d(${deltaX}px, ${deltaY}px, 0)`;
                        tituloDestaque.offsetHeight;
                        tituloDestaque.style.transition = 'transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1)';
                        tituloDestaque.style.transform  = 'translate3d(0, 0, 0)';
                    }
                } else if (tituloDestaque) {
                    tituloDestaque.style.transform = 'none';
                }

                if (autoriaDestaque) {
                    autoriaDestaque.style.opacity   = '0';
                    autoriaDestaque.style.transform = 'translateY(10px)';
                    timersNavegacao.push(setTimeout(() => {
                        autoriaDestaque.style.transition = 'all 0.6s cubic-bezier(0.165, 0.84, 0.44, 1)';
                        autoriaDestaque.style.opacity    = '1';
                        autoriaDestaque.style.transform  = 'translateY(0)';
                    }, 150));
                }

                if (descricao) {
                    descricao.style.opacity = '0';
                    const descInterno = descricao.querySelector('.texto-descricao-interno');
                    const filhosDesc  = descInterno ? Array.from(descInterno.children) : Array.from(descricao.children);

                    filhosDesc.forEach(filho => {
                        filho.style.overflow      = 'hidden';
                        filho.style.paddingBottom = '4px';
                        filho.innerHTML = `<span class="linha-animada" style="display:block;transform:translateY(110%);transition:none;">${filho.innerHTML}</span>`;
                    });

                    descricao.offsetHeight;
                    descricao.style.opacity = '1';
                    const tempoEspera = tituloVoou ? 400 : 50;

                    filhosDesc.forEach((filho, idx) => {
                        const span = filho.querySelector('.linha-animada');
                        if (!span) return;
                        timersNavegacao.push(setTimeout(() => {
                            span.style.transition = 'transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1)';
                            span.style.transform  = 'translateY(0)';
                            timersNavegacao.push(setTimeout(() => {
                                filho.style.overflow = 'visible';
                                filho.innerHTML      = span.innerHTML;
                            }, 650));
                        }, tempoEspera + (idx * 40)));
                    });
                }
            }

            if (isIndoParaLista) {
                const linksTela = lateral.querySelectorAll('.item-projeto a');

                if (temTituloNaTela && cloneDescida && rectInicialDescida && linkAlvoIndex !== -1) {
                    const linkAlvoTela = linksTela[linkAlvoIndex];
                    const rectAlvo     = linkAlvoTela.getBoundingClientRect();
                    const deltaY       = rectAlvo.top  - rectInicialDescida.top;
                    const deltaX       = rectAlvo.left - rectInicialDescida.left;

                    if (Math.abs(deltaY) > 5) {
                        Object.assign(cloneDescida.style, {
                            position: 'fixed', top: rectInicialDescida.top + 'px',
                            left: rectInicialDescida.left + 'px', margin: '0',
                            pointerEvents: 'none', zIndex: '9999',
                        });
                        document.body.appendChild(cloneDescida);
                        cloneDescida.offsetHeight;
                        cloneDescida.style.transition = 'transform 0.6s cubic-bezier(0.165, 0.84, 0.44, 1)';
                        cloneDescida.style.transform  = `translate3d(${deltaX}px, ${deltaY}px, 0)`;

                        cloneDescida.addEventListener('transitionend', (e) => {
                            if (e.propertyName === 'transform') { linkAlvoTela.style.opacity = '1'; cloneDescida.remove(); }
                        }, { once: true });

                        timersNavegacao.push(setTimeout(() => {
                            if (cloneDescida.parentNode) { linkAlvoTela.style.opacity = '1'; cloneDescida.remove(); }
                        }, 800));
                    } else {
                        linkAlvoTela.style.opacity = '1';
                    }
                }

                linksTela.forEach((a, idx) => {
                    if (idx !== linkAlvoIndex) {
                        a.offsetHeight;
                        timersNavegacao.push(setTimeout(() => {
                            a.style.transition = 'transform 0.6s ease, opacity 0.6s ease, color 0.3s ease';
                            a.style.transform  = 'translateY(0)';
                            a.style.opacity    = '1';
                            timersNavegacao.push(setTimeout(() => { a.style.transition = ''; a.style.transform = ''; a.style.opacity = ''; }, 650));
                        }, idx * 40));
                    } else {
                        timersNavegacao.push(setTimeout(() => { a.style.transition = ''; a.style.transform = ''; a.style.opacity = ''; }, 650));
                    }
                });
            }

            const navTopoNova  = doc.querySelector('.navegacao-topo');
            const navTopoAtual = document.querySelector('.navegacao-topo');
            if (navTopoNova && navTopoAtual) navTopoAtual.innerHTML = navTopoNova.innerHTML;

            const voltarPendente = navegacaoPendente?.tipo === 'pagina' && !navegacaoPendente.atualizarHistorico;
            if (atualizarHistorico && !voltarPendente) window.history.pushState({}, '', url);

            if (!isIndoParaLista) {
                const projetoAberto = document.body.classList.contains('single');
                projetoAtivoUrlGlobal = projetoAberto ? url : null;
                filtroAtivoGlobal     = projetoAberto ? null : new URL(url, window.location.href).searchParams.get('categoria');
                atualizarNegritoFiltros(filtroAtivoGlobal);
                fichaTecnicaCache     = projetoAberto ? doc.querySelector('.container-dinamico-lateral').innerHTML : null;
                resetarInteracoes();
            } else {
                filtroAtivoGlobal = new URL(url, window.location.href).searchParams.get('categoria');
                atualizarNegritoFiltros(filtroAtivoGlobal);
                marcarProjetoAtivoNaLista();
            }

        } catch (error) {
            window.location.href = url;
        } finally {
            if (ehMobile()) liberarNavegacao();
            else timersNavegacao.push(setTimeout(liberarNavegacao, 900));
        }
    }

    async function toggleLateral(url, slugClicado) {
        if (isCarregando) {
            navegacaoPendente = { tipo: 'filtro', url, slug: slugClicado };
            return;
        }

        if (filtroAtivoGlobal === slugClicado) {
            if (!document.body.classList.contains('single') || !fichaTecnicaCache) {
                carregarPagina(urlSemFiltro(url), true, true);
                return;
            }

            limparTimers();
            isCarregando = true;
            lateral.style.opacity = '0';
            timersNavegacao.push(setTimeout(() => {
                lateral.innerHTML     = fichaTecnicaCache;
                lateral.style.opacity = '1';
                const descricao = lateral.querySelector('.texto-descricao-lateral');
                const autoria   = lateral.querySelector('.autoria-projeto');
                if (descricao) descricao.style.opacity = '1';
                if (autoria) autoria.style.opacity = '1';
                filtroAtivoGlobal     = null;
                atualizarNegritoFiltros(null);
                projetoAtivoUrlGlobal = urlSemFiltro(projetoAtivoUrlGlobal);
                const voltarPendente = navegacaoPendente?.tipo === 'pagina' && !navegacaoPendente.atualizarHistorico;
                if (!voltarPendente) window.history.pushState({}, '', projetoAtivoUrlGlobal);
                marcarProjetoAtivoNaLista();
                initLenisLateral();
                liberarNavegacao();
            }, 200));
            return;
        }

        carregarPagina(url, true, true);
    }

    function marcarProjetoAtivoNaLista() {
        if (!projetoAtivoUrlGlobal) return;
        let urlObj;
        try { urlObj = new URL(projetoAtivoUrlGlobal); } catch (e) { return; }
        const urlPura = urlSemFiltro(urlObj.href);
        document.querySelectorAll('.item-projeto a').forEach(a => {
            a.parentElement.classList.toggle('projeto-ativo', urlSemFiltro(a.href) === urlPura);
        });
    }

    function atualizarNegritoFiltros(slug) {
        document.querySelectorAll('.filtros-categoria a').forEach(a => {
            a.classList.toggle('filtro-ativo', a.dataset.slug === slug);
        });
    }

    // =========================================================================
    // CARRINHO VIA SHOPIFY (STOREFRONT / CART API)
    // =========================================================================
    const SHOPIFY_API_VERSAO = '2026-07';
    const CART_STORAGE_KEY   = 'tiete_cart_id';

    // Fragmento GraphQL reutilizado por todas as mutações/queries de carrinho.
    const CART_FIELDS = `
        id
        checkoutUrl
        totalQuantity
        cost { subtotalAmount { amount currencyCode } }
        lines(first: 50) {
            edges { node {
                id
                quantity
                merchandise { ... on ProductVariant {
                    id
                    title
                    price { amount currencyCode }
                    product { title handle featuredImage { url altText } }
                } }
            } }
        }`;

    async function shopifyGraphQL(query, variables) {
        const dominio = window.temaConfig?.shopifyDomain || '';
        const token   = window.temaConfig?.shopifyToken  || '';
        if (!dominio || !token) return null;

        const resp = await fetch(`https://${dominio}/api/${SHOPIFY_API_VERSAO}/graphql.json`, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Shopify-Storefront-Access-Token': token,
            },
            body: JSON.stringify({ query, variables }),
        });
        return resp.json();
    }

    // ---- Estado / helpers ----
    const getCartId = () => { try { return localStorage.getItem(CART_STORAGE_KEY) || ''; } catch (e) { return ''; } };
    const setCartId = id => { try { id ? localStorage.setItem(CART_STORAGE_KEY, id) : localStorage.removeItem(CART_STORAGE_KEY); } catch (e) {} };

    function formatarDinheiro(amount, currency) {
        const v = parseFloat(amount || 0);
        if (currency === 'BRL') return 'R$ ' + v.toFixed(2).replace('.', ',');
        try { return new Intl.NumberFormat(undefined, { style: 'currency', currency: currency || 'BRL' }).format(v); }
        catch (e) { return (currency || '') + ' ' + v.toFixed(2); }
    }

    async function resolverVariante(handle) {
        const q = `query($handle: String!) {
            product(handle: $handle) { selectedOrFirstAvailableVariant { id availableForSale } }
        }`;
        const j = await shopifyGraphQL(q, { handle });
        return j?.data?.product?.selectedOrFirstAvailableVariant || null;
    }

    // ---- Operações de carrinho (retornam o objeto cart normalizado) ----
    async function cartCreate(variantId, quantity, cupom) {
        const m = `mutation($lines: [CartLineInput!]!, $discountCodes: [String!]) {
            cartCreate(input: { lines: $lines, discountCodes: $discountCodes }) {
                cart { ${CART_FIELDS} } userErrors { message }
            }
        }`;
        const vars = { lines: [{ merchandiseId: variantId, quantity }] };
        if (cupom) vars.discountCodes = [cupom];
        const j = await shopifyGraphQL(m, vars);
        return j?.data?.cartCreate?.cart || null;
    }

    async function cartLinesAdd(cartId, variantId, quantity) {
        const m = `mutation($cartId: ID!, $lines: [CartLineInput!]!) {
            cartLinesAdd(cartId: $cartId, lines: $lines) { cart { ${CART_FIELDS} } userErrors { message } }
        }`;
        const j = await shopifyGraphQL(m, { cartId, lines: [{ merchandiseId: variantId, quantity }] });
        return j?.data?.cartLinesAdd?.cart || null;
    }

    async function cartLinesUpdate(cartId, lineId, quantity) {
        const m = `mutation($cartId: ID!, $lines: [CartLineUpdateInput!]!) {
            cartLinesUpdate(cartId: $cartId, lines: $lines) { cart { ${CART_FIELDS} } userErrors { message } }
        }`;
        const j = await shopifyGraphQL(m, { cartId, lines: [{ id: lineId, quantity }] });
        return j?.data?.cartLinesUpdate?.cart || null;
    }

    async function cartLinesRemove(cartId, lineId) {
        const m = `mutation($cartId: ID!, $lineIds: [ID!]!) {
            cartLinesRemove(cartId: $cartId, lineIds: $lineIds) { cart { ${CART_FIELDS} } userErrors { message } }
        }`;
        const j = await shopifyGraphQL(m, { cartId, lineIds: [lineId] });
        return j?.data?.cartLinesRemove?.cart || null;
    }

    async function cartFetch(cartId) {
        const q = `query($cartId: ID!) { cart(id: $cartId) { ${CART_FIELDS} } }`;
        const j = await shopifyGraphQL(q, { cartId });
        return j?.data?.cart || null;
    }

    // ---- Ação: adicionar ao carrinho (botão .btn-comprar-shopify) ----
    async function adicionarAoCarrinho(btn) {
        const handle         = btn.dataset.produto;
        const erroEl         = btn.parentElement.querySelector('.curso-compra-erro');
        const checkEstudante = btn.parentElement.querySelector('.curso-input-estudante');
        const cupom          = (checkEstudante?.checked && window.temaConfig?.cupomEstudante) || '';
        if (!handle || btn.disabled) return;

        const textoOriginal = btn.textContent;
        btn.disabled = true;
        btn.textContent = '···';
        if (erroEl) erroEl.textContent = '';

        try {
            const variante = await resolverVariante(handle);
            if (!variante || !variante.availableForSale) throw new Error('indisponivel');

            let cartId = getCartId();
            let cart = null;
            if (cartId) {
                cart = await cartLinesAdd(cartId, variante.id, 1);
                if (!cart) { setCartId(''); cartId = ''; } // carrinho expirou → recria
            }
            if (!cart) {
                cart = await cartCreate(variante.id, 1, cupom);
                if (cart) setCartId(cart.id);
            }
            if (!cart) throw new Error('cart_falhou');

            atualizarCarrinhoUI(cart);
            abrirDrawer();
        } catch (e) {
            if (erroEl) erroEl.textContent = window.temaConfig?.erroCompra || 'Erro ao adicionar ao carrinho.';
        } finally {
            btn.disabled = false;
            btn.textContent = textoOriginal;
        }
    }

    // ---- UI do carrinho (drawer) ----
    const T = () => (window.temaConfig?.carrinhoTextos || {});

    function abrirDrawer() {
        document.getElementById('carrinhoDrawer')?.classList.add('aberto');
        document.getElementById('carrinhoOverlay')?.classList.add('aberto');
    }
    function fecharDrawer() {
        document.getElementById('carrinhoDrawer')?.classList.remove('aberto');
        document.getElementById('carrinhoOverlay')?.classList.remove('aberto');
    }

    function atualizarCarrinhoUI(cart) {
        const toggle = document.getElementById('carrinhoToggle');
        const badge  = document.getElementById('carrinhoContador');
        const itens  = document.getElementById('carrinhoItens');
        const subEl  = document.getElementById('carrinhoSubtotal');
        const btnCk  = document.getElementById('carrinhoCheckout');
        if (!itens) return;

        const qtdTotal = cart?.totalQuantity || 0;
        if (badge)  badge.textContent = qtdTotal;
        if (toggle) toggle.style.display = qtdTotal > 0 ? 'flex' : 'none';

        if (!cart || qtdTotal === 0) {
            itens.innerHTML = `<p class="carrinho-vazio">${T().vazio || 'Carrinho vazio'}</p>`;
            if (subEl) subEl.textContent = formatarDinheiro(0, 'BRL');
            if (btnCk) { btnCk.disabled = true; btnCk.dataset.url = ''; }
            return;
        }

        const linhas = (cart.lines?.edges || []).map(({ node }) => {
            const m     = node.merchandise || {};
            const img   = m.product?.featuredImage?.url;
            const nome  = m.product?.title || '';
            const varT  = (m.title && m.title !== 'Default Title') ? m.title : '';
            const preco = formatarDinheiro((parseFloat(m.price?.amount || 0) * node.quantity), m.price?.currencyCode);
            return `<div class="carrinho-item" data-line="${node.id}">
                <div class="carrinho-item-img">${img ? `<img src="${img}" alt="">` : ''}</div>
                <div class="carrinho-item-info">
                    <p class="carrinho-item-nome">${nome}</p>
                    ${varT ? `<p class="carrinho-item-var">${varT}</p>` : ''}
                    <div class="carrinho-stepper">
                        <button type="button" data-acao="menos" aria-label="-">–</button>
                        <span>${node.quantity}</span>
                        <button type="button" data-acao="mais" aria-label="+">+</button>
                    </div>
                    <button type="button" class="carrinho-remover" data-acao="remover">${T().remover || 'Remover'}</button>
                </div>
                <div class="carrinho-item-preco">${preco}</div>
            </div>`;
        }).join('');
        itens.innerHTML = linhas;

        if (subEl) subEl.textContent = formatarDinheiro(cart.cost?.subtotalAmount?.amount, cart.cost?.subtotalAmount?.currencyCode);
        if (btnCk) { btnCk.disabled = false; btnCk.dataset.url = cart.checkoutUrl || ''; }
    }

    async function mudarLinha(lineId, novaQtd) {
        const cartId = getCartId();
        if (!cartId) return;
        const cart = novaQtd <= 0
            ? await cartLinesRemove(cartId, lineId)
            : await cartLinesUpdate(cartId, lineId, novaQtd);
        if (cart) atualizarCarrinhoUI(cart);
    }

    // Inicializa o badge a partir de um carrinho já existente (sessões anteriores).
    (async () => {
        const cartId = getCartId();
        if (!cartId) return;
        const cart = await cartFetch(cartId);
        if (cart) atualizarCarrinhoUI(cart); else setCartId('');
    })();

    // Delegação de eventos do drawer (toggle, fechar, stepper, remover, checkout).
    document.addEventListener('click', (e) => {
        if (e.target.closest('#carrinhoToggle'))  { abrirDrawer(); return; }
        if (e.target.closest('#carrinhoFechar') || e.target.closest('#carrinhoOverlay')) { fecharDrawer(); return; }

        const ck = e.target.closest('#carrinhoCheckout');
        if (ck) { if (ck.dataset.url) window.location.href = ck.dataset.url; return; }

        const acaoBtn = e.target.closest('[data-acao]');
        if (acaoBtn && acaoBtn.closest('.carrinho-item')) {
            const item = acaoBtn.closest('.carrinho-item');
            const lineId = item.dataset.line;
            const qtdAtual = parseInt(item.querySelector('.carrinho-stepper span')?.textContent || '0', 10);
            const acao = acaoBtn.dataset.acao;
            if (acao === 'mais')    mudarLinha(lineId, qtdAtual + 1);
            if (acao === 'menos')   mudarLinha(lineId, qtdAtual - 1);
            if (acao === 'remover') mudarLinha(lineId, 0);
        }
    });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') fecharDrawer(); });

    // =========================================================================
    // CENTRAL DE CLIQUES
    // =========================================================================
    document.addEventListener('click', (e) => {

        const easterEgg = e.target.closest('#logoEasterEgg');
        if (easterEgg) {
            posicionarLogoEasterEgg();
            logoAbertaNaEntradaMobile = false;
            const ativo = easterEgg.classList.toggle('easter-egg-ativo');
            easterEgg.setAttribute('aria-pressed', String(ativo));
            return;
        }

        if (ehMobile()) {
            const link = e.target.closest('a');
            if (!link || link.target === '_blank' || link.origin !== window.location.origin) return;
            if (link.closest('.mobile-menu-project-link, .home-mobile-project, .mobile-menu-home')) {
                if (isCarregando) return;
                e.preventDefault();
                carregarPagina(link.href, true, false, link);
            }
            return;
        }

        const dot = e.target.closest('.snap-dot');
        if (dot) { snapParaSecaoIndexHome(parseInt(dot.dataset.index, 10)); return; }

        const btnComprar = e.target.closest('.btn-comprar-shopify');
        if (btnComprar) { adicionarAoCarrinho(btnComprar); return; }

        if (document.body.classList.contains('single')) {
            const img = e.target.closest('.conteudo-projeto img');

            if (img) {
                e.preventDefault();
                if (zoomAtivo) {
                    desativarZoomViaClique();
                } else {
                    ativarZoom(img);
                }
                return;
            }

            if (e.target.id === 'barraZoom') {
                desativarZoomViaClique();
                return;
            }
        }

        const link = e.target.closest('a');
        if (!link || !link.href || !link.href.includes(window.location.hostname)) return;
        if (link.classList.contains('btn-idioma')) return;

        if (link.closest('.filtros-categoria')) {
            if (link.classList.contains('link-externo')) return;
            e.preventDefault();
            toggleLateral(link.href, link.dataset.slug);
        } else if (link.closest('.menu-projetos') || link.closest('.btn-home-ajax')) {
            e.preventDefault();
            carregarPagina(link.href, true, false, link);
        }
    });

    // =========================================================================
    // HOME SCROLL SNAP — lógica
    // =========================================================================
    function getSnapSections() {
        return [
            document.getElementById('secaoCapa'),
            document.getElementById('secaoSobre'),
            document.getElementById('secaoYayoi'),
            document.getElementById('secaoAlbum'),
        ].filter(Boolean);
    }

    function atualizarDots(index) {
        document.querySelectorAll('.snap-dot').forEach((dot, i) => {
            dot.classList.toggle('snap-dot--ativo', i === index);
        });
    }

    function snapParaSecaoIndexHome(index) {
        if (ehMobile()) return;
        if (homeSnapCooldown) return;
        const sections = getSnapSections();
        if (index < 0 || index >= sections.length) return;

        homeSnapCooldown = true;
        homeSnapIndex    = index;
        atualizarDots(index);

        lenis.scrollTo(sections[index], {
            duration: 1.2,
            easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
        });

        setTimeout(() => { homeSnapCooldown = false; }, 1300);
    }

    function snapParaSecaoHome(delta) {
        snapParaSecaoIndexHome(homeSnapIndex + (delta > 0 ? 1 : -1));
    }

    area.addEventListener('wheel', (e) => {
        if (ehMobile() || !document.body.classList.contains('home') || zoomAtivo) return;

        // 🟢 ZONA LIVRE DE SCROLL: Verifica se o scroll está dentro da nossa caixa de texto
        const scrollBox = e.target.closest('.sobre-texto-scroll');
        if (scrollBox) {
            const isScrollingDown = e.deltaY > 0;
            const isScrollingUp = e.deltaY < 0;
            // +1px de tolerância para evitar bugs de arredondamento em monitores Retina
            const isAtTop = scrollBox.scrollTop <= 0;
            const isAtBottom = (scrollBox.scrollHeight - scrollBox.scrollTop) <= (scrollBox.clientHeight + 1);

            // Se houver texto para ler (não bateu no topo/fundo), nós abortamos a função do SNAP
            // e deixamos o navegador rolar o texto naturalmente!
            if ((isScrollingDown && !isAtBottom) || (isScrollingUp && !isAtTop)) {
                e.stopPropagation();
                return; 
            }
        }

        // Se chegou aqui, faz o SNAP normal pulando de seção
        e.preventDefault();
        e.stopPropagation();
        snapParaSecaoHome(e.deltaY);
    }, { passive: false, capture: true });

    let touchStartY = 0;
    let isTouchInScrollBox = false; // Flag para rastrear o dedo no celular

    area.addEventListener('touchstart', (e) => {
        if (ehMobile() || !document.body.classList.contains('home') || zoomAtivo) return;
        touchStartY = e.touches[0].clientY;
        
        isTouchInScrollBox = !!e.target.closest('.sobre-texto-scroll');
    }, { passive: true });

    area.addEventListener('touchmove', (e) => {
        if (ehMobile() || !document.body.classList.contains('home') || zoomAtivo) return;
        
        if (isTouchInScrollBox) return;

        e.preventDefault();
    }, { passive: false });

    area.addEventListener('touchend', (e) => {
        if (ehMobile() || !document.body.classList.contains('home') || zoomAtivo) return;
        
        if (isTouchInScrollBox) return;

        const deltaY = touchStartY - e.changedTouches[0].clientY;
        if (Math.abs(deltaY) < 40) return;
        snapParaSecaoHome(deltaY);
    }, { passive: true });

    // =========================================================================
    // ALBUM DA SEMANA (NOVA LÓGICA DE CARROSSEL)
    // =========================================================================

    function inicializarAlbum() {
        if (ehMobile() || !document.getElementById('secaoAlbum')) return;
        inicializarPlayerAudio();
        inicializarCarrosselArquivo(); // Substituiu o antigo inicializarArquivo()
    }

    function inicializarPlayerAudio() {
        const playerEl = document.getElementById('albumPlayer');
        if (!playerEl) return;

        const playBtn = document.getElementById('albumPlayBtn');
        const track   = document.getElementById('albumProgressTrack');
        const src = playerEl.dataset.src || '';

        // Se o utilizador abriu um disco diferente, trocamos o som. Se for o mesmo, deixamos tocar!
        if (src && !globalAudio.src.endsWith(src)) {
            globalAudio.src = src;
        }

        // Sincroniza o botão de Play/Pause caso a música já esteja tocando em background
        if (!globalAudio.paused && globalAudio.currentTime > 0) {
            playBtn.innerHTML = SVG_PAUSE;
            playBtn.setAttribute('aria-label', 'Pausar');
            playBtn.classList.add('tocando');
        } else {
            playBtn.innerHTML = SVG_PLAY;
            playBtn.setAttribute('aria-label', 'Tocar');
            playBtn.classList.remove('tocando');
        }

        // Botão Play/Pause
        playBtn.addEventListener('click', () => {
            if (!globalAudio.src) return;
            if (globalAudio.paused) {
                playBtn.textContent = '…'; // Feedback de loading
                globalAudio.play().then(() => {
                    playBtn.innerHTML = SVG_PAUSE;
                    playBtn.setAttribute('aria-label', 'Pausar');
                    playBtn.classList.add('tocando');
                }).catch((err) => {
                    console.error('[Album player]', err);
                    playBtn.innerHTML = SVG_PLAY;
                });
            } else {
                globalAudio.pause();
                playBtn.innerHTML = SVG_PLAY;
                playBtn.setAttribute('aria-label', 'Tocar');
                playBtn.classList.remove('tocando');
            }
        });

        // Clique na barra de progresso
        track.addEventListener('click', (e) => {
            if (!globalAudio.duration) return;
            const rect = track.getBoundingClientRect();
            globalAudio.currentTime = ((e.clientX - rect.left) / rect.width) * globalAudio.duration;
        });
    }

    // NOVA FUNÇÃO: Controla o carrossel horizontal de miniaturas da base
    function inicializarCarrosselArquivo() {
        const slider = document.querySelector('.album-slider-arquivos');
        const btnPrev = document.querySelector('.btn-prev');
        const btnNext = document.querySelector('.btn-next');

        if (slider && btnPrev && btnNext) {
            // Lógica das setas de navegação
            btnNext.addEventListener('click', () => {
                slider.scrollBy({ left: 150, behavior: 'smooth' });
            });
            btnPrev.addEventListener('click', () => {
                slider.scrollBy({ left: -150, behavior: 'smooth' });
            });

            // Lógica de clique na miniatura usando Delegação de Eventos
            slider.addEventListener('click', (e) => {
                const miniCard = e.target.closest('.album-card-mini');
                if (!miniCard) return;
                
                const albumId = parseInt(miniCard.dataset.id, 10);
                if (albumId) {
                    carregarAlbumAjax(albumId);
                }
            });
        }
    }

    function carregarAlbumAjax(id) {
        if (typeof temaConfig === 'undefined' || !temaConfig.ajaxUrl) return;
        fetch(`${temaConfig.ajaxUrl}?action=get_album_semana&id=${id}`)
            .then(r => r.json())
            .then(({ success, data }) => {
                if (success && data) atualizarDomAlbum(data);
            })
            .catch(() => {});
    }

    function atualizarDomAlbum(data) {
        // 1. Atualizar Capa Grande
        const img = document.querySelector('#albumDisc img');
        if (img) { img.src = data.cover_url || ''; img.alt = data.titulo || ''; }

        // 2. Atualizar Player de Áudio (Agora usando o Global)
        const playBtn  = document.getElementById('albumPlayBtn');
        const fill     = document.getElementById('albumProgressFill');
        const tempo    = document.getElementById('albumTempo');
        const playerEl = document.getElementById('albumPlayer');
        
        if (globalAudio) {
            globalAudio.pause();
            globalAudio.src = data.audio_url || '';
        }
        
        if (playBtn)  { 
            playBtn.innerHTML = SVG_PLAY;
            playBtn.classList.remove('tocando'); 
            playBtn.setAttribute('aria-label', 'Tocar'); 
            playBtn.disabled = !data.audio_url; 
        }
        if (fill)     fill.style.width = '0';
        if (tempo)    tempo.textContent = data.audio_url ? '0:00' : '—';
        if (playerEl) playerEl.dataset.src = data.audio_url || '';

        // 3. Atualizar Textos (Novo Layout Agrupado)
        const artistaEl = document.querySelector('.album-artista');
        const tituloEl  = document.querySelector('.album-titulo-novo'); 
        
        if (artistaEl) {
            artistaEl.textContent = data.artista + (data.ano ? ', ' + data.ano : '');
        }
        if (tituloEl) {
            tituloEl.textContent = data.titulo || '';
        }

        // 4. Atualizar Review
        const review = document.getElementById('albumReview');
        if (review) review.innerHTML = data.review_html || '';

        // 5. Atualizar Faixa Destaque (Nova Lógica que substituiu a Tracklist)
        const faixaNomeEl = document.querySelector('.faixa-nome');
        const faixaContainer = document.querySelector('.album-faixa-selecionada');
        
        if (data.faixa_destaque) {
            if (faixaNomeEl) faixaNomeEl.textContent = data.faixa_destaque;
            if (faixaContainer) faixaContainer.style.display = 'block';
        } else {
            if (faixaContainer) faixaContainer.style.display = 'none';
        }

        // 6. Atualizar Streaming links
        const streamingEl = document.getElementById('albumStreaming');
        if (streamingEl) {
            streamingEl.innerHTML = (data.streaming_links || [])
                .map(l => `<a href="${l.url}" target="_blank" rel="noopener noreferrer">${l.name}</a>`)
                .join('');
        }

        // 7. Extrair cor da nova capa 
        if (typeof extrairCorCapa === 'function') {
            extrairCorCapa();
        }
    }

    function resetarInteracoes() {
        snapSairDoZoom();

        if (ehMobile()) {
            marcarProjetoAtivoNaLista();
            if (window.location.hash) {
                const destino = document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
                requestAnimationFrame(() => destino?.scrollIntoView());
            } else {
                window.scrollTo(0, 0);
            }
            permitirsSumico = true;
            return;
        }

        if (document.body.classList.contains('home')) {
            homeSnapIndex    = 0;
            homeSnapCooldown = false;
            atualizarDots(0);
            sincronizarOffsetImagem();
        }

        permitirsSumico = false;
        lenis.scrollTo(0, { immediate: true });

        const isSingle = document.body.classList.contains('single');
        const firstImg = area.querySelector('img');

        marcarProjetoAtivoNaLista();

        if (isSingle && firstImg) {
            const center = () => {
                const target = firstImg.offsetTop - (area.clientHeight / 2) + (firstImg.offsetHeight / 2);
                lenis.scrollTo(target, {
                    duration: 1.5,
                    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                });
                timersNavegacao.push(setTimeout(() => { permitirsSumico = true; }, 1500));
            };
            timersNavegacao.push(setTimeout(center, 250));
        } else {
            permitirsSumico = true;
        }
    }

    const onScroll = (e) => {
        if (zoomAtivo && !isZoomAnimating && !transitandoZoom && barWidthAtual > 10) {
            if (Math.abs(e.animatedScroll - scrollBaseZoom) > 5) {
                desativarZoomViaClique();
            }
        }

        if (!permitirsSumico) return;
    };
    lenis.on('scroll', onScroll);

    mobileLayout.addEventListener('change', () => {
        definirMenuMobile(false);
        lenis.destroy();
        lenis = new Lenis({
            wrapper: ehMobile() ? window : area,
            lerp: 0.06,
            wheelMultiplier: 1.2,
            smoothWheel: !ehMobile(),
        });
        lenis.on('scroll', onScroll);
        initLenisLateral();
        lenis.resize();
        posicionarLogoEasterEgg();
    });

    // =========================================================================
    // LOAD INICIAL E SPLASH SCREEN
    // =========================================================================
    window.addEventListener('load', () => {
        sincronizarOffsetImagem();
        inicializarAlbum();
        if (ehMobile()) document.getElementById('introOverlay')?.remove();
        const isSingle = document.body.classList.contains('single');

        if (isSingle) {
            const desc    = lateral.querySelector('.texto-descricao-lateral');
            const autoria = lateral.querySelector('.autoria-projeto');
            if (desc)    desc.style.opacity    = '1';
            if (autoria) autoria.style.opacity = '1';
            filtroAtivoGlobal = null;
            atualizarNegritoFiltros(null);
            resetarInteracoes();
            initLenisLateral();
        } else {
            const intro = document.getElementById('introOverlay');
            if (intro) {
                setTimeout(() => { intro.classList.add('animar'); }, 100);
                setTimeout(() => {
                    intro.classList.add('ocultar');
                    setTimeout(() => { resetarInteracoes(); intro.remove(); }, 800);
                }, 2000);
            } else {
                resetarInteracoes();
            }
        }
    });

    window.addEventListener('popstate', () => carregarPagina(window.location.href, false));

});
