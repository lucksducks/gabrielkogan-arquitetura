document.addEventListener('DOMContentLoaded', () => {

    // =========================================================================
    // REFERÊNCIAS DOM E ESTADO GLOBAL
    // =========================================================================
    const area      = document.getElementById('mainContent');
    const indicator = document.getElementById('scrollIndicator');
    const lateral   = document.querySelector('.container-dinamico-lateral');
    const barraZoom = document.getElementById('barraZoom');

    let permitirsSumico       = false;
    let isCarregando          = false;
    let fichaTecnicaCache     = document.body.classList.contains('single') ? lateral.innerHTML : null;
    let filtroAtivoGlobal     = temaConfig.filtroAtivo;
    let projetoAtivoUrlGlobal = document.body.classList.contains('single') ? window.location.href : null;

    let zoomAtivo       = false;
    let imagemZoomAtual = null;
    let scrollBaseZoom  = 0;

    let movePreviewRef  = null;
    let timersNavegacao = [];

    function limparTimers() {
        timersNavegacao.forEach(clearTimeout);
        timersNavegacao = [];
        
        if (movePreviewRef) {
            window.removeEventListener('mousemove', movePreviewRef);
            movePreviewRef = null;
        }
        
        // Mata clones zumbis garantindo a limpeza
        document.querySelectorAll('.clone-titulo-animacao').forEach(el => el.remove());
    }

    // =========================================================================
    // ZOOM DE IMAGEM — ARQUITETURA DE INTENÇÃO (O Padrão Ouro)
    // =========================================================================

    const easeInOutQuint = (t) => t < 0.5 ? 16 * t * t * t * t * t : 1 - Math.pow(-2 * t + 2, 5) / 2;
    let isZoomAnimating = false;

    // A MÁGICA 1: O Snap de Saída (Quando o usuário rola a página)
    function snapSairDoZoom() {
        if (!zoomAtivo || isZoomAnimating) return;

        const img = imagemZoomAtual;
        const viewportY = img.getBoundingClientRect().top; // Onde a foto está na tela agora?

        // 1. Desliga tudo instantaneamente (Snap)
        zoomAtivo = false;
        document.body.classList.remove('is-zoom-active');
        barraZoom.classList.remove('is-active');

        area.style.paddingLeft = '0px';
        barraZoom.style.width = '0px';

        // 2. Ancoragem Silenciosa: Atualiza o scroll para a foto não sair do lugar
        const newDocY = img.offsetTop;
        lenis.scrollTo(newDocY - viewportY, { immediate: true });
        lenis.resize();

        imagemZoomAtual = null;
    }

    // A MÁGICA 2: O Motor de Animação Cinematográfica (Quando o usuário clica)
    function runZoomAnimation(img, startY, targetY, startPadding, targetPadding, isEntering) {
        isZoomAnimating = true;
        if (isEntering) zoomAtivo = true;
        imagemZoomAtual = img;

        if (isEntering) barraZoom.classList.add('is-active');
        
        // TRAVA DE SEGURANÇA: O usuário não pode rolar enquanto o layout se move
        lenis.stop(); 

        const duration = 700; // 0.7s para elegância
        const startTime = performance.now();

        function frame(time) {
            const elapsed = time - startTime;
            const progress = Math.min(elapsed / duration, 1);
            const eased = easeInOutQuint(progress);

            // Anima a largura
            const currentPadding = startPadding + (targetPadding - startPadding) * eased;
            area.style.paddingLeft = currentPadding + 'px';
            barraZoom.style.width = currentPadding + 'px';

            // Ancora a câmera milimetricamente frame a frame
            const actualY = img.getBoundingClientRect().top;
            const expectedY = startY + (targetY - startY) * eased;
            const diff = actualY - expectedY;

            area.scrollTop += diff;
            lenis.scrollTo(area.scrollTop, { immediate: true });

            if (progress < 1) {
                requestAnimationFrame(frame);
            } else {
                isZoomAnimating = false;
                if (isEntering) {
                    document.body.classList.add('is-zoom-active');
                    scrollBaseZoom = lenis.scroll; // Grava o chão para o gatilho de rolar
                } else {
                    document.body.classList.remove('is-zoom-active');
                    barraZoom.classList.remove('is-active');
                    zoomAtivo = false;
                    imagemZoomAtual = null;
                }
                lenis.start(); // DEVOLVE o scroll para o usuário
                lenis.resize();
            }
        }
        requestAnimationFrame(frame);
    }

    // GATILHO: Entrar no Zoom (Clique)
    function ativarZoom(img) {
        if (!img || zoomAtivo || isZoomAnimating) return;

        const startY = img.getBoundingClientRect().top;
        const areaWidth = area.offsetWidth;
        const r = img.naturalWidth / img.naturalHeight;

        if (r >= 1) return; // Trava para focar apenas em fotos verticais

        const targetContentWidth = window.innerHeight * r;
        const barWidth = areaWidth - targetContentWidth;
        
        if (barWidth < 10) return;

        barraZoom.style.setProperty('--bar-width', barWidth + 'px');

        const startPadding = 0;
        const targetPadding = barWidth;
        const targetY = 0; // A foto vai para o teto da tela
        
        runZoomAnimation(img, startY, targetY, startPadding, targetPadding, true);
    }

    // GATILHO: Sair do Zoom (Clique)
    function desativarZoomViaClique() {
        if (!zoomAtivo || isZoomAnimating) return;

        const img = imagemZoomAtual;
        const startY = img.getBoundingClientRect().top;
        const startPadding = parseFloat(area.style.paddingLeft) || 0;
        const targetPadding = 0;

        // Tática "Measure First" com REFLOW forçado (Acaba com a piscada!)
        area.style.paddingLeft = '0px';
        area.offsetHeight; // NUNCA remova isso. É o que obriga o navegador a processar o CSS.
        const targetHeight = img.offsetHeight;
        
        area.style.paddingLeft = startPadding + 'px'; 
        area.offsetHeight; // Força o retorno antes de começar o loop

        // Calcula exatamente onde a foto deve parar para ficar centralizada
        const targetY = (window.innerHeight / 2) - (targetHeight / 2);

        runZoomAnimation(img, startY, targetY, startPadding, targetPadding, false);
    }
    // =========================================================================
    // LENIS — SMOOTH SCROLL (Motor Manual Restaurado)
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
    // HOVER PREVIEW — DELEGAÇÃO DE EVENTOS (À prova de AJAX / Sem Memory Leak)
    // =========================================================================
    
    // Mouseover bolha pelo DOM. Ouvimos no document para não perder referência no AJAX.
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
                movePreviewRef(e); // Aciona imediatamente para a primeira posição
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
                cloneDescida.classList.add('clone-titulo-animacao'); // FIX 2 APLICADO: Classe inserida no clone
                
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
                    desativarZoomViaClique(); // Faz a animação cinematográfica
                } else {
                    ativarZoom(img);
                }
                return;
            }
            
            // Clicar na barra preta também desativa (mantemos como fallback de segurança)
            if (e.target.id === 'barraZoom') {
                desativarZoom();
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
    // BOTÃO "SOBRE"
    // =========================================================================
    if (indicator) {
        indicator.addEventListener('click', () => {
            const secaoSobre = document.getElementById('secaoSobre');
            if (secaoSobre) {
                lenis.scrollTo(secaoSobre, {
                    duration: 1.5,
                    easing: (t) => Math.min(1, 1.001 - Math.pow(2, -10 * t)),
                });
            }
        });
    }

    // =========================================================================
    // SCROLL — revelação e indicadores
    // =========================================================================
    function resetarInteracoes() {
        desativarZoom(); 

        if (document.body.classList.contains('home')) {
            const secaoSobre = document.getElementById('secaoSobre');
            if (secaoSobre) secaoSobre.classList.remove('revelada');
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
            if (indicator) indicator.style.opacity = '1';
        }
    }

    // FIX 3: Salva referência da função onScroll e atrela
    const onScroll = (e) => {
        // LÓGICA DE DESARME DO ZOOM PELO SCROLL
        if (zoomAtivo && !isZoomAnimating) {
            if (Math.abs(e.animatedScroll - scrollBaseZoom) > 40) {
                snapSairDoZoom(); // Snap instantâneo, não bloqueia o dedo do usuário
            }
        }

        if (!permitirsSumico) return;
        const isHome = document.body.classList.contains('home');
        if (indicator) {
            indicator.style.opacity = (isHome && e.animatedScroll <= 100) ? '1' : '0';
        }
        if (isHome) {
            const secaoSobre = document.getElementById('secaoSobre');
            if (secaoSobre && e.animatedScroll > 10) {
                secaoSobre.classList.add('revelada');
                if (indicator) indicator.style.pointerEvents = 'none';
            }
        }
    };
    lenis.on('scroll', onScroll);

    // =========================================================================
    // LOAD INICIAL E SPLASH SCREEN
    // =========================================================================
    window.addEventListener('load', () => {
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