<?php
/**
 * Gabriel Kogan Arquitetura - Funções e Definições
 * Tema modularizado
 */

// 1. Configurações básicas e carregamento de scripts/estilos
require get_template_directory() . '/inc/setup.php';

// 2. Meta Boxes gerais do site (Tradução e BG-Zoom)
require get_template_directory() . '/inc/meta-boxes.php';

// 3. Custom Post Type "Álbum da Semana" e suas lógicas internas
require get_template_directory() . '/inc/cpt-album.php';

// 4. Integração com APIs externas (Spotify, Deezer, iTunes)
require get_template_directory() . '/inc/api-spotify.php';