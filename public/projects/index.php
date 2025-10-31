<?php
require_once '../../config/config.php';
require_once '../../includes/auth_functions.php';
require_once '../../includes/project_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$projectManager = new ProjectManager();
$myProjects = $projectManager->getUserProjects($_SESSION['usuario']['id']);
$invitedProjects = $projectManager->getInvitedProjects($_SESSION['usuario']['id']);
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Meus Projetos - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>
    
    <div class="main-container">
        <div class="section-header">
            <h1>Meus Projetos</h1>
            <a href="create.php" class="btn btn-primary">+ Novo Projeto</a>
        </div>
        
        <?php if (empty($myProjects) && empty($invitedProjects)): ?>
            <div class="empty-state">
                <h2>Nenhum projeto encontrado</h2>
                <p>Comece criando seu primeiro projeto!</p>
                <a href="create.php" class="btn btn-primary">Criar Projeto</a>
            </div>
        <?php else: ?>
            <!-- Meus Projetos (como proprietário) -->
            <?php if (!empty($myProjects)): ?>
                <div class="projects-section">
                    <h2>Meus Projetos</h2>
                    <div class="projects-grid">
                        <?php foreach ($myProjects as $project): ?>
                            <div class="project-card" style="border-left: 4px solid <?= $project['cor'] ?>">
                                <div class="project-header">
                                    <h3><?= htmlspecialchars($project['nome']) ?></h3>
                                    <span class="project-status status-<?= $project['status'] ?>">
                                        <?= ucfirst($project['status']) ?>
                                    </span>
                                </div>
                                
                                <p class="project-description"><?= htmlspecialchars($project['descricao'] ?? 'Sem descrição') ?></p>
                                
                                <div class="project-stats">
                                    <span>Tarefas: <?= $project['total_tarefas'] ?></span>
                                    <span>Concluídas: <?= $project['tarefas_concluidas'] ?></span>
                                </div>
                                
                                <div class="project-meta">
                                    <small>Criado em: <?= date('d/m/Y', strtotime($project['data_criacao'])) ?></small>
                                    <?php if ($project['data_limite']): ?>
                                        <small class="deadline">📅 <?= date('d/m/Y', strtotime($project['data_limite'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="project-actions">
                                    <a href="view.php?id=<?= $project['id'] ?>" class="btn">Ver</a>
                                    <a href="../kanban/board.php?project=<?= $project['id'] ?>" class="btn btn-primary">Kanban</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Projetos Convidados -->
            <?php if (!empty($invitedProjects)): ?>
                <div class="projects-section">
                    <h2>Projetos Convidados</h2>
                    <div class="projects-grid">
                        <?php foreach ($invitedProjects as $project): ?>
                            <div class="project-card invited-project" style="border-left: 4px solid <?= $project['cor'] ?>">
                                <div class="project-header">
                                    <h3><?= htmlspecialchars($project['nome']) ?></h3>
                                    <div class="project-badges">
                                        <span class="project-status status-<?= $project['status'] ?>">
                                            <?= ucfirst($project['status']) ?>
                                        </span>
                                        <span class="permission-badge permission-<?= $project['nivel_permissao'] ?>">
                                            <?= ucfirst($project['nivel_permissao']) ?>
                                        </span>
                                    </div>
                                </div>
                                
                                <p class="project-description"><?= htmlspecialchars($project['descricao'] ?? 'Sem descrição') ?></p>
                                
                                <div class="project-stats">
                                    <span>Tarefas: <?= $project['total_tarefas'] ?></span>
                                    <span>Concluídas: <?= $project['tarefas_concluidas'] ?></span>
                                </div>
                                
                                <div class="project-meta">
                                    <small>Criado em: <?= date('d/m/Y', strtotime($project['data_criacao'])) ?></small>
                                    <?php if ($project['data_limite']): ?>
                                        <small class="deadline">📅 <?= date('d/m/Y', strtotime($project['data_limite'])) ?></small>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="project-actions">
                                    <a href="view.php?id=<?= $project['id'] ?>" class="btn">Ver</a>
                                    <a href="../kanban/board.php?project=<?= $project['id'] ?>" class="btn btn-primary">Kanban</a>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
    
    <style>
        .projects-section {
            margin-bottom: 40px;
        }
        .projects-section h2 {
            margin-bottom: 20px;
            color: var(--dark);
            font-size: 24px;
            border-bottom: 2px solid #e1e1e1;
            padding-bottom: 10px;
        }
        .project-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 10px;
        }
        .project-badges {
            display: flex;
            gap: 8px;
        }
        .permission-badge {
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .permission-leitura {
            background: #e3f2fd;
            color: #1565c0;
        }
        .permission-edicao {
            background: #fff3e0;
            color: #ef6c00;
        }
        .permission-administrador {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .project-description {
            color: #666;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        .project-meta {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
            font-size: 12px;
            color: #888;
        }
        .deadline {
            background: #fff3cd;
            padding: 2px 6px;
            border-radius: 4px;
            font-weight: 500;
        }
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .empty-state h2 {
            color: #666;
            margin-bottom: 10px;
        }
        .empty-state p {
            color: #888;
            margin-bottom: 20px;
        }
        .invited-project {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
        }
        .projects-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        .project-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            transition: transform 0.2s ease;
        }
        .project-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.15);
        }
        .project-stats {
            display: flex;
            gap: 15px;
            margin-bottom: 10px;
            font-size: 14px;
            color: #666;
        }
        .project-actions {
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 8px 16px;
            background: #f8f9fa;
            color: #333;
            text-decoration: none;
            border-radius: 5px;
            border: 1px solid #ddd;
            font-size: 14px;
            transition: all 0.3s ease;
        }
        .btn-primary {
            background: var(--primary, #007bff);
            color: white;
            border-color: var(--primary, #007bff);
        }
        .btn:hover {
            opacity: 0.9;
            transform: translateY(-1px);
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .project-status {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
        }
        .status-ativo {
            background: #e8f5e8;
            color: #2e7d32;
        }
        .status-concluido {
            background: #e3f2fd;
            color: #1565c0;
        }
        .status-cancelado {
            background: #ffebee;
            color: #c62828;
        }
    </style>
</body>
</html>