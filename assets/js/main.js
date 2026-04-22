document.addEventListener('DOMContentLoaded', () => {

    // =========================================================================
    // REFERÊNCIAS DOM E ESTADO GLOBAL
    // =========================================================================
    const area      = document.getElementById('mainContent');
    const indicator = document.getElementById('scrollIndicator');
    const lateral   = document.querySelector('.container-dinamico-lateral');
    const barraZoom = document.getElementById('barraZoom');

    // ⭐ Garantir que a imagem bg-zoom dinâmica está configurada
    const bgZoomUrl = barraZoom?.dataset.bgZoomUrl;
    if (bgZoomUrl) {
        barraZoom.style.backgroundImage = `url('${bgZoomUrl}')`;
    }

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
        if (!document.body.classList.contains('home')) return;
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

    function limparTimers() {
        timersNavegacao.forEach(clearTimeout);
        timersNavegacao = [];

        if (movePreviewRef) {
            window.removeEventListener('mousemove', movePreviewRef);
            movePreviewRef = null;
        }

        document.querySelectorAll('.clone-titulo-animacao').forEach(el => el.remove());
    }

    // =========================================================================
    // ZOOM DE IMAGEM
    // =========================================================================

    const easeInOutQuint = (t) => t < 0.5 ? 16 * t * t * t * t * t : 1 - Math.pow(-2 * t + 2, 5) / 2;
    let isZoomAnimating = false;

    // FIX 2: recebe flag isTransitioning para evitar lenis.resize() durante transição entre zooms
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

        // FIX 2: só recalcula o scroll quando saindo do zoom definitivamente,
        // não quando está transitando para outra imagem (evita o salto de posição)
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

        // FIX 2: passa isTransitioning=true para evitar lenis.resize() durante a troca
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

        // FIX 1: imagem pequena (sem espaço real para barra) → só scroll para o topo,
        // sem entrar no estado de zoom. Evita zoomAtivo=true com barWidthAtual=0,
        // que impedia o guard de scroll de disparar e causava o salto ao clicar a próxima imagem.
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

        // FIX 3: guard contra imagemZoomAtual null (race condition defensivo)
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
    const lenis = new Lenis({
        wrapper:         area,
        lerp:            0.06,
        wheelMultiplier: 1.2,
        smoothWheel:     true,
    });

    let lenisLateralInstance = null;

    function initLenisLateral() {
        if (lenisLateralInstance) {
            lenisLateralInstance.destroy();
            lenisLateralInstance = null;
        }
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
    document.addEventListener('mouseover', (e) => {
        const item = e.target.closest('.item-projeto');
        if (!item || item.classList.contains('projeto-ativo')) return;

        const thumbsOverlay = document.querySelector('.area-scroll-thumbs');
        const prevHoverBox  = document.getElementById('prevHover');
        const targetThumb   = thumbsOverlay?.querySelector(`[data-projeto-id="${item.dataset.projetoId}"]`);

        if (targetThumb && prevHoverBox) {
            document.querySelectorAll('.prev-hover-img').forEach(t => t.classList.remove('thumb-ativo'));
            targetThumb.classList.add('thumb-ativo');
            thumbsOverlay.classList.add('ativo');

            if (!movePreviewRef) {
                movePreviewRef = (ev) => {
                    const boxWidth = prevHoverBox.offsetWidth;
                    prevHoverBox.style.transform = `translate3d(${ev.clientX - (boxWidth / 2) - 180}px, ${ev.clientY + 20}px, 0)`;
                };
                window.addEventListener('mousemove', movePreviewRef);
                movePreviewRef(e);
            }
        }
    });

    document.addEventListener('mouseout', (e) => {
        const item = e.target.closest('.item-projeto');
        if (!item) return;

        const thumbsOverlay = document.querySelector('.area-scroll-thumbs');
        if (thumbsOverlay) thumbsOverlay.classList.remove('ativo');
        document.querySelectorAll('.prev-hover-img').forEach(t => t.classList.remove('thumb-ativo'));

        if (movePreviewRef) {
            window.removeEventListener('mousemove', movePreviewRef);
            movePreviewRef = null;
        }
    });

    // =========================================================================
    // NAVEGAÇÃO AJAX
    // =========================================================================
    async function carregarPagina(url, atualizarHistorico = true, apenasLateral = false, elementoClicado = null) {
        if (isCarregando) return;

        limparTimers();
        snapSairDoZoom(); // isTransitioning=false → chama lenis.resize() normalmente
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
                area.innerHTML          = doc.querySelector('#mainContent').innerHTML;
                document.body.className = doc.body.className;
                lenis.resize();
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

            const tituloDestaque  = lateral.querySelector('.titulo-projeto-destaque');
            const autoriaDestaque = lateral.querySelector('.autoria-projeto');
            const descricao       = lateral.querySelector('.texto-descricao-lateral');

            if (!isIndoParaLista) {
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

            if (atualizarHistorico) window.history.pushState({}, '', url);

            if (!isIndoParaLista) {
                projetoAtivoUrlGlobal = url;
                filtroAtivoGlobal     = null;
                atualizarNegritoFiltros(null);
                fichaTecnicaCache     = lateral.innerHTML;
                resetarInteracoes();
            } else {
                marcarProjetoAtivoNaLista();
            }

        } catch (error) {
            window.location.href = url;
        } finally {
            timersNavegacao.push(setTimeout(() => {
                isCarregando = false;
            }, 900));
        }
    }

    async function toggleLateral(url, slugClicado) {
        if (isCarregando) return;

        if (filtroAtivoGlobal === slugClicado && fichaTecnicaCache) {
            limparTimers();
            lateral.style.opacity = '0';
            timersNavegacao.push(setTimeout(() => {
                lateral.innerHTML     = fichaTecnicaCache;
                lateral.style.opacity = '1';
                filtroAtivoGlobal     = null;
                atualizarNegritoFiltros(null);
                marcarProjetoAtivoNaLista();
                initLenisLateral();
            }, 200));
            return;
        }

        filtroAtivoGlobal = slugClicado;
        carregarPagina(url, true, true);
    }

    function marcarProjetoAtivoNaLista() {
        if (!projetoAtivoUrlGlobal) return;
        let urlObj;
        try { urlObj = new URL(projetoAtivoUrlGlobal); } catch (e) { return; }
        const urlPura = urlObj.origin + urlObj.pathname + urlObj.search;
        document.querySelectorAll('.item-projeto a').forEach(a => {
            a.parentElement.classList.toggle('projeto-ativo', (a.origin + a.pathname + a.search) === urlPura);
        });
    }

    function atualizarNegritoFiltros(slug) {
        document.querySelectorAll('.filtros-categoria a').forEach(a => {
            a.classList.toggle('filtro-ativo', a.dataset.slug === slug);
        });
    }

    // =========================================================================
    // CENTRAL DE CLIQUES
    // =========================================================================
    document.addEventListener('click', (e) => {

        const easterEgg = e.target.closest('#logoEasterEgg');
        if (easterEgg) { easterEgg.classList.toggle('easter-egg-ativo'); return; }

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
        if (!document.body.classList.contains('home') || zoomAtivo) return;
        e.preventDefault();
        e.stopPropagation();
        snapParaSecaoHome(e.deltaY);
    }, { passive: false, capture: true });

    let touchStartY = 0;

    area.addEventListener('touchstart', (e) => {
        if (!document.body.classList.contains('home') || zoomAtivo) return;
        touchStartY = e.touches[0].clientY;
    }, { passive: true });

    area.addEventListener('touchmove', (e) => {
        if (!document.body.classList.contains('home') || zoomAtivo) return;
        e.preventDefault();
    }, { passive: false });

    area.addEventListener('touchend', (e) => {
        if (!document.body.classList.contains('home') || zoomAtivo) return;
        const deltaY = touchStartY - e.changedTouches[0].clientY;
        if (Math.abs(deltaY) < 40) return;
        snapParaSecaoHome(deltaY);
    }, { passive: true });

    // =========================================================================
    // CLIQUE NOS DOTS DE NAVEGAÇÃO
    // =========================================================================
    if (indicator) {
        indicator.addEventListener('click', (e) => {
            const dot = e.target.closest('.snap-dot');
            if (!dot) return;
            snapParaSecaoIndexHome(parseInt(dot.dataset.index, 10));
        });
    }

    // =========================================================================
    // SCROLL — revelação e indicadores
    // =========================================================================
    // =========================================================================
    // ALBUM DA SEMANA
    // =========================================================================

    function inicializarAlbum() {
        if (!document.getElementById('secaoAlbum')) return;
        inicializarPlayerAudio();
        inicializarArquivo();
    }

    function inicializarPlayerAudio() {
        const playerEl = document.getElementById('albumPlayer');
        if (!playerEl) return;

        const audio   = document.getElementById('albumAudio');
        const playBtn = document.getElementById('albumPlayBtn');
        const fill    = document.getElementById('albumProgressFill');
        const track   = document.getElementById('albumProgressTrack');
        const tempo   = document.getElementById('albumTempo');

        const SVG_PLAY  = '<svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><polygon points="2,1 9,5 2,9"/></svg>';
        const SVG_PAUSE = '<svg viewBox="0 0 10 10" width="10" height="10" fill="currentColor"><rect x="1.5" y="1" width="2.5" height="8"/><rect x="6" y="1" width="2.5" height="8"/></svg>';

        const src = playerEl.dataset.src || '';
        if (src) audio.src = src;

        playBtn.addEventListener('click', () => {
            if (!audio.src || audio.src === window.location.href) return;
            if (audio.paused) {
                playBtn.textContent = '…';
                audio.play()
                    .then(() => {
                        playBtn.innerHTML = SVG_PAUSE;
                        playBtn.setAttribute('aria-label', 'Pausar');
                        playBtn.classList.add('tocando');
                    })
                    .catch((err) => {
                        console.error('[Album player]', err);
                        playBtn.innerHTML = SVG_PLAY;
                        tempo.textContent = 'erro';
                    });
            } else {
                audio.pause();
                playBtn.innerHTML = SVG_PLAY;
                playBtn.setAttribute('aria-label', 'Tocar');
                playBtn.classList.remove('tocando');
            }
        });

        audio.addEventListener('timeupdate', () => {
            if (!audio.duration) return;
            fill.style.width = (audio.currentTime / audio.duration * 100) + '%';
            const s = Math.floor(audio.currentTime);
            tempo.textContent = `${~~(s / 60)}:${String(s % 60).padStart(2, '0')}`;
        });

        track.addEventListener('click', (e) => {
            if (!audio.duration) return;
            const rect = track.getBoundingClientRect();
            audio.currentTime = ((e.clientX - rect.left) / rect.width) * audio.duration;
        });

        audio.addEventListener('ended', () => {
            playBtn.innerHTML = SVG_PLAY;
            playBtn.classList.remove('tocando');
            fill.style.width = '0';
            tempo.textContent = '0:00';
        });
    }

    function inicializarArquivo() {
        const btnAbrir  = document.getElementById('albumArquivoBtn');
        const overlay   = document.getElementById('albumArquivoOverlay');
        const btnFechar = document.getElementById('albumArquivoFechar');
        const grid      = document.getElementById('albumArquivoGrid');
        if (!btnAbrir || !overlay || !grid) return;

        const arquivo = (typeof temaConfig !== 'undefined' && temaConfig.albumArquivo) ? temaConfig.albumArquivo : [];

        if (arquivo.length > 0) {
            grid.innerHTML = arquivo.map(a => `
                <div class="album-card" data-id="${parseInt(a.id, 10)}">
                    <div class="album-card-capa">
                        <img src="${a.thumb_url}" alt="${String(a.titulo).replace(/"/g, '&quot;')}" loading="lazy">
                    </div>
                    <div class="album-card-titulo">${a.titulo}</div>
                    <div class="album-card-artista">${a.artista} &middot; ${a.ano}</div>
                </div>
            `).join('');

            grid.addEventListener('click', (e) => {
                const card = e.target.closest('.album-card');
                if (!card) return;
                carregarAlbumAjax(parseInt(card.dataset.id, 10));
                fecharArquivo();
            });
        } else {
            grid.innerHTML = '<p style="color:var(--cor-texto-inativo);font-size:12px;">Nenhum álbum anterior.</p>';
        }

        const fecharArquivo = () => {
            overlay.classList.remove('visivel');
            overlay.setAttribute('aria-hidden', 'true');
        };

        btnAbrir.addEventListener('click', () => {
            overlay.classList.add('visivel');
            overlay.setAttribute('aria-hidden', 'false');
        });

        btnFechar.addEventListener('click', fecharArquivo);

        document.addEventListener('keydown', (e) => {
            if (e.key === 'Escape' && overlay.classList.contains('visivel')) fecharArquivo();
        });
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
        // Capa
        const img = document.querySelector('#albumDisc img');
        if (img) { img.src = data.cover_url || ''; img.alt = data.titulo || ''; }

        // Player — parar e trocar src
        const audio    = document.getElementById('albumAudio');
        const playBtn  = document.getElementById('albumPlayBtn');
        const fill     = document.getElementById('albumProgressFill');
        const tempo    = document.getElementById('albumTempo');
        const playerEl = document.getElementById('albumPlayer');
        if (audio)    { audio.pause(); audio.src = data.audio_url || ''; }
        if (playBtn)  { playBtn.classList.remove('tocando'); playBtn.setAttribute('aria-label', 'Tocar'); playBtn.disabled = !data.audio_url; }
        if (fill)     fill.style.width = '0';
        if (tempo)    tempo.textContent = data.audio_url ? '0:00' : '—';
        if (playerEl) playerEl.dataset.src = data.audio_url || '';

        // Textos
        const artista = document.querySelector('.album-artista');
        const titulo  = document.querySelector('.album-titulo');
        const meta    = document.querySelector('.album-meta');
        if (artista) artista.textContent = data.artista || '';
        if (titulo)  titulo.textContent  = data.titulo  || '';
        if (meta)    meta.textContent    = [data.ano, data.genero].filter(Boolean).join(' · ');

        // Review
        const review = document.getElementById('albumReview');
        if (review) review.innerHTML = data.review_html || '';

        // Tracklist — faixa_destaque highlighted via CSS class
        const ol = document.querySelector('#albumTracklist ol');
        if (ol) {
            const hintNorm = (data.faixa_destaque || '').toLowerCase().replace(/[^a-z0-9]/g, '');
            ol.innerHTML = (data.tracklist || []).map(t => {
                const tNorm = t.toLowerCase().replace(/[^a-z0-9]/g, '');
                const destaque = hintNorm && tNorm &&
                    (tNorm.includes(hintNorm) || hintNorm.includes(tNorm));
                return `<li${destaque ? ' class="faixa-destaque"' : ''}>${t}</li>`;
            }).join('');
        }

        // Streaming links
        const streamingEl = document.getElementById('albumStreaming');
        if (streamingEl) {
            streamingEl.innerHTML = (data.streaming_links || [])
                .map(l => `<a href="${l.url}" target="_blank" rel="noopener noreferrer">${l.name}</a>`)
                .join('');
        }

        // Re-extrai cor da nova capa
        extrairCorCapa();
    }

    function resetarInteracoes() {
        snapSairDoZoom();

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

    // =========================================================================
    // LOAD INICIAL E SPLASH SCREEN
    // =========================================================================
    window.addEventListener('load', () => {
        sincronizarOffsetImagem();
        inicializarAlbum();
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