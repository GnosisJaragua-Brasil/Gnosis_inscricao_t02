<?php
// 1. Conexão
$host = "sql211.byethost4.com";
$user = "b4_40736935";
$pass = "W85855858d@@";
$db   = "b4_40736935_cronograma";

$conn = new mysqli($host, $user, $pass, $db);
$conn->set_charset("utf8mb4");

// 2. Funções de Suporte
function extrair_videos($text) {
    $pattern = '/(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|m\.youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)(?:[^\s]*))/i';
    preg_match_all($pattern, $text, $matches);
    $links = array_unique($matches[1]); 
    $texto_limpo = preg_replace($pattern, '', $text);
    return ['texto' => trim($texto_limpo), 'links' => $links];
}

function normalizar($string) {
    $mapa = ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ã'=>'a','õ'=>'o','â'=>'a','ê'=>'e','î'=>'i','ô'=>'o','û'=>'u','ç'=>'c'];
    $string = strtr(mb_strtolower($string, 'UTF-8'), $mapa);
    $string = preg_replace('/[^a-z0-9]/', '_', $string);
    return ($string == 'material_didatico') ? 'material' : $string;
}

// 3. Carga de Dados
$txt_res = $conn->query("SELECT * FROM configuracoes");
$txt = []; 
while($t = @$txt_res->fetch_assoc()) { $txt[normalizar($t['chave'])] = $t; }

$materiais_res = $conn->query("SELECT * FROM materiais");
$pdfs = []; 
while($m = @$materiais_res->fetch_assoc()) { $pdfs[$m['aula_id']][$m['tipo']] = $m['caminho_arquivo']; }

function renderizarAulas($conn, $categoria, $pdfs) {
    $stmt = $conn->prepare("SELECT * FROM aulas WHERE camera = ? ORDER BY numero_aula ASC");
    $stmt->bind_param("s", $categoria);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($res->num_rows == 0) return print '<div class="col-12 text-center py-4 text-muted small">Nenhuma aula disponível.</div>';
    while($a = $res->fetch_assoc()) {
        $res_midia = extrair_videos($a['descricao'] ?? '');
        $data_f = date('d/m', strtotime($a['data_aula']));
        $img = $a['imagem_url'] ?: 'https://via.placeholder.com/300x150';
        echo '<div class="col-6 col-md-4 mb-3">
            <div class="card card-aula h-100 border-0 shadow-sm">
                <img src="'.$img.'" class="img-capa" onclick=\'ver('.json_encode($a).','.json_encode($res_midia['links']).')\'>
                <div class="p-2 p-md-3">
                    <div class="agendamento">'.$data_f.' - '.substr($a['hora'], 0, 5).'</div>
                    <h6 class="fw-bold mb-2 title-limit">'.$a['titulo'].'</h6>
                    <div class="d-flex gap-2">'.(isset($pdfs[$a['numero_aula']]['principal']) ? '<a href="'.$pdfs[$a['numero_aula']]['principal'].'" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:10px">PDF</a>' : '');
                    foreach($res_midia['links'] as $l) echo '<a href="'.$l.'" target="_blank" class="text-danger"><i class="fab fa-youtube"></i></a>';
        echo '</div></div></div></div>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt-br">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gnosis Brasil</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --primary-dark: #005475ff; --accent-blue: #005bd2; }
        body { background: #f4f7fa; font-family: 'Segoe UI', sans-serif; }
        
        /* Navbar & OffCanvas Customization */
        .navbar-dark-custom { background-color: var(--primary-dark); padding: 15px 0; }
        .offcanvas { background-color: var(--primary-dark); color: white; width: 280px; }
        .offcanvas .nav-link { color: #ccc; font-size: 1.1rem; padding: 15px 20px; border-bottom: 1px solid #333; }
        .offcanvas .nav-link:hover { color: white; background: #222; }
        .offcanvas .nav-link i { width: 30px; }

        /* Carousel */
        .carousel-item img { height: 450px; object-fit: cover; filter: brightness(0.6); }
        .carousel-caption { bottom: 20%; }

        /* Estilos Gerais */
        .card-aula { border-radius: 15px; overflow: hidden; transition: 0.3s; }
        .img-capa { height: 130px; object-fit: cover; width: 100%; cursor: pointer; }
        .agendamento { font-size: 11px; color: var(--accent-blue); fw-bold; }
        .title-limit { font-size: 14px; height: 2.8em; overflow: hidden; }
        .info-card { background: white; border-radius: 15px; padding: 15px; border-left: 4px solid var(--accent-blue); box-shadow: 0 4px 10px rgba(0,0,0,0.05); }
    </style>
</head>
<body>

    <nav class="navbar navbar-dark-custom shadow-sm sticky-top">
        <div class="container">
            <button class="navbar-toggler border-0" type="button" data-bs-toggle="offcanvas" data-bs-target="#offcanvasNavbar">
                <span class="navbar-toggler-icon"></span>
            </button>
            <a class="navbar-brand fw-bold" href="#">GNOSIS BRASIL</a>
            <div class="d-none d-md-block text-white-50 small">Cronograma de Estudos</div>
        </div>
    </nav>

    <div class="offcanvas offcanvas-start" tabindex="-1" id="offcanvasNavbar">
        <div class="offcanvas-header border-bottom border-secondary">
            <h5 class="offcanvas-title fw-bold">MENU</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body p-0">
            <nav class="nav flex-column">
                <a class="nav-link" href="#"><i class="fas fa-home"></i> Início</a>
                <a class="nav-link" href="admin_upload.php" target="_blank"><i class="fas fa-user-shield"></i> Painel Admin</a>
                <a class="nav-link" href="#"><i class="fas fa-book"></i> Biblioteca</a>
                <a class="nav-link" href="#"><i class="fas fa-calendar-alt"></i> Agenda Completa</a>
                <div class="p-4 mt-5">
                    <small class="text-muted text-uppercase">Informações</small>
                    <p class="small text-white-50 mt-2">Portal dedicado ao estudo da Gnosis no Brasil.</p>
                </div>
            </nav>
        </div>
    </div>

    <div id="carouselDestaques" class="carousel slide" data-bs-ride="carousel">
        <div class="carousel-inner">
            <?php 
            $slides = $conn->query("SELECT * FROM carrossel ORDER BY id DESC");
            $first = true;
            if($slides->num_rows > 0) {
                while($s = $slides->fetch_assoc()) {
                    echo '<div class="carousel-item '.($first?'active':'').'">
                            <img src="'.$s['imagem_url'].'" class="d-block w-100">
                            <div class="carousel-caption text-center">
                                <h1 class="fw-bold">'.$s['titulo'].'</h1>
                                <p class="lead">'.$s['subtitulo'].'</p>
                            </div>
                          </div>';
                    $first = false;
                }
            } else {
                echo '<div class="carousel-item active"><img src="https://via.placeholder.com/1200x500?text=Gnosis+Brasil" class="d-block w-100"></div>';
            }
            ?>
        </div>
    </div>

    <div class="container mt-4">
        <div class="row g-3 mb-5">
            <div class="col-12"><div class="info-card"><h6>CURSO</h6><p class="small mb-0"><?php echo @$txt['curso_gnosis']['conteudo']; ?></p></div></div>
            <div class="col-md-6"><div class="info-card"><h6>MATERIAL DIDÁTICO</h6><p class="small mb-0"><?php echo @$txt['material']['conteudo']; ?></p></div></div>
            <div class="col-md-6"><div class="info-card"><h6>OBJETIVOS</h6><p class="small mb-0"><?php echo @$txt['objetivos']['conteudo']; ?></p></div></div>
        </div>

        <ul class="nav nav-pills justify-content-center mb-4 gap-2" role="tablist">
            <li class="nav-item"><a class="nav-link active shadow-sm" data-bs-toggle="pill" href="#basica">Básica</a></li>
            <li class="nav-item"><a class="nav-link shadow-sm" data-bs-toggle="pill" href="#avancada">Avançada</a></li>
            <li class="nav-item"><a class="nav-link shadow-sm" data-bs-toggle="pill" href="#meditacao">Meditação</a></li>
            <li class="nav-item"><a class="nav-link shadow-sm" data-bs-toggle="pill" href="#especial">Especial</a></li>
        </ul>

        <div class="tab-content pb-5">
            <div id="basica" class="tab-pane active fade show"><div class="row g-3"><?php renderizarAulas($conn, 'basica', $pdfs); ?></div></div>
            <div id="avancada" class="tab-pane fade"><div class="row g-3"><?php renderizarAulas($conn, 'avancada', $pdfs); ?></div></div>
            <div id="meditacao" class="tab-pane fade"><div class="row g-3"><?php renderizarAulas($conn, 'meditacao', $pdfs); ?></div></div>
            <div id="especial" class="tab-pane fade"><div class="row g-3"><?php renderizarAulas($conn, 'especial', $pdfs); ?></div></div>
        </div>
    </div>

    <div class="modal fade" id="modalDet" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 rounded-4 overflow-hidden">
                <img id="m-img" src="" style="height:200px; object-fit:cover;">
                <div class="modal-body p-4">
                    <h5 id="m-tit" class="fw-bold text-primary mb-3"></h5>
                    <p id="m-des" class="text-muted small" style="white-space: pre-wrap;"></p>
                    <div id="m-yt-list" class="d-grid gap-2 mt-3"></div>
                </div>
                <div class="p-3"><button class="btn btn-dark w-100 rounded-pill" data-bs-dismiss="modal">Fechar</button></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function ver(a, links) {
            document.getElementById('m-img').src = a.imagem_url || 'https://via.placeholder.com/300x150';
            document.getElementById('m-tit').innerText = a.titulo;
            let desc = a.descricao || "";
            const regexYt = /(https?:\/\/(?:www\.)?(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/|m\.youtube\.com\/watch\?v=)([a-zA-Z0-9_-]+)(?:[^\s]*))/gi;
            document.getElementById('m-des').innerText = desc.replace(regexYt, "").trim();
            const list = document.getElementById('m-yt-list'); 
            list.innerHTML = "";
            links.forEach((l, i) => { 
                list.innerHTML += `<a href="${l}" target="_blank" class="btn btn-danger py-2 rounded-pill fw-bold"><i class="fab fa-youtube me-2"></i>Vídeo ${i+1}</a>`; 
            });
            new bootstrap.Modal(document.getElementById('modalDet')).show();
        }
    </script>
</body>
</html>