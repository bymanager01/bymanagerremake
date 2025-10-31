<?php
require_once '../config/config.php';
require_once '../includes/auth_functions.php';
require_once '../includes/project_functions.php';
require_once '../includes/statistics_functions.php';

$auth = new Auth();
if (!$auth->isLoggedIn()) {
    redirect(BASE_URL . '/auth/login.php');
}

$projectManager = new ProjectManager();
$statsManager = new StatisticsManager();

$projects = $projectManager->getUserProjects($_SESSION['usuario']['id']);
$statistics = $statsManager->getUserStatistics($_SESSION['usuario']['id']);
$topProjects = $statsManager->getTopPerformingProjects($_SESSION['usuario']['id']);
$upcomingDeadlines = $statsManager->getUpcomingDeadlines($_SESSION['usuario']['id']);
$recentActivity = $statsManager->getRecentActivity($_SESSION['usuario']['id']);

// Calcular métricas
$progresso_geral = $statistics['tarefas']['total_tarefas'] > 0 ? 
    round(($statistics['tarefas']['tarefas_concluidas'] / $statistics['tarefas']['total_tarefas']) * 100) : 0;
?>

<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="main-container">
        <!-- Header do Dashboard -->
        <div class="dashboard-header">
            <div>
                <h1>Bem-vindo, <?= $_SESSION['usuario']['nome'] ?>! 👋</h1>
                <p>Resumo do seu progresso hoje</p>
            </div>
            <div class="header-actions">
                <a href="projects/create.php" class="btn btn-primary">+ Novo Projeto</a>
                <a href="tasks/create.php" class="btn">+ Nova Tarefa</a>
            </div>
        </div>

        <!-- Métricas Principais -->
        <div class="metrics-grid">
            <div class="metric-card">
                <div class="metric-icon" style="background: #e3f2fd; color: #1976d2;">
                    📊
                </div>
                <div class="metric-info">
                    <h3>Progresso Geral</h3>
                    <span class="metric-value"><?= $progresso_geral ?>%</span>
                    <div class="progress-bar">
                        <div class="progress-fill" style="width: <?= $progresso_geral ?>%"></div>
                    </div>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: #e8f5e9; color: #2e7d32;">
                    ✅
                </div>
                <div class="metric-info">
                    <h3>Tarefas Concluídas</h3>
                    <span class="metric-value"><?= $statistics['tarefas']['tarefas_concluidas'] ?? 0 ?></span>
                    <p>de <?= $statistics['tarefas']['total_tarefas'] ?? 0 ?> total</p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: #fff3e0; color: #f57c00;">
                    ⚠️
                </div>
                <div class="metric-info">
                    <h3>Tarefas Atrasadas</h3>
                    <span class="metric-value" style="color: #e53935;">
                        <?= $statistics['tarefas']['tarefas_atrasadas'] ?? 0 ?>
                    </span>
                    <p>precisam de atenção</p>
                </div>
            </div>
            
            <div class="metric-card">
                <div class="metric-icon" style="background: #f3e5f5; color: #7b1fa2;">
                    📁
                </div>
                <div class="metric-info">
                    <h3>Projetos Ativos</h3>
                    <span class="metric-value"><?= $statistics['projetos']['projetos_ativos'] ?? 0 ?></span>
                    <p>em andamento</p>
                </div>
            </div>
        </div>

        <!-- Grid Principal -->
        <div class="dashboard-grid">
            <!-- Projetos em Destaque -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>🏆 Projetos em Destaque</h2>
                    <a href="projects/index.php" class="btn-link">Ver todos</a>
                </div>
                <div class="projects-list">
                    <?php foreach ($topProjects as $project): ?>
                        <div class="project-item">
                            <div class="project-color" style="background: <?= $project['cor'] ?>"></div>
                            <div class="project-info">
                                <h4><?= htmlspecialchars($project['nome']) ?></h4>
                                <p><?= $project['percentual_concluido'] ?>% concluído</p>
                                <div class="progress-bar small">
                                    <div class="progress-fill" style="width: <?= $project['percentual_concluido'] ?>%"></div>
                                </div>
                            </div>
                            <div class="project-stats">
                                <span><?= $project['tarefas_concluidas'] ?>/<?= $project['total_tarefas'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($topProjects)): ?>
                        <p class="empty-message">Nenhum projeto com tarefas ainda</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Prazos Próximos -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>📅 Prazos Próximos</h2>
                    <a href="tasks/index.php" class="btn-link">Ver todas</a>
                </div>
                <div class="deadlines-list">
                    <?php foreach ($upcomingDeadlines as $task): ?>
                        <div class="deadline-item <?= $task['dias_restantes'] <= 2 ? 'urgent' : '' ?>">
                            <div class="task-priority priority-<?= $task['prioridade'] ?>">
                                <?= strtoupper(substr($task['prioridade'], 0, 1)) ?>
                            </div>
                            <div class="task-info">
                                <h4><?= htmlspecialchars($task['titulo']) ?></h4>
                                <p>
                                    <span class="project-badge" style="background: <?= $task['projeto_cor'] ?>">
                                        <?= htmlspecialchars($task['projeto_nome']) ?>
                                    </span>
                                </p>
                            </div>
                            <div class="deadline-info">
                                <span class="days-left <?= $task['dias_restantes'] <= 2 ? 'danger' : '' ?>">
                                    <?= $task['dias_restantes'] ?> dias
                                </span>
                                <small><?= date('d/m', strtotime($task['data_limite'])) ?></small>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($upcomingDeadlines)): ?>
                        <p class="empty-message">Nenhum prazo próximo 🎉</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Atividade Recente -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>📝 Atividade Recente</h2>
                </div>
                <div class="activity-list">
                    <?php foreach ($recentActivity as $activity): ?>
                        <div class="activity-item">
                            <div class="activity-icon">
                                <?php 
                                $icons = [
                                    'login' => '🔐',
                                    'logout' => '🚪',
                                    'criar_projeto' => '📁',
                                    'criar_tarefa' => '✅',
                                    'atualizar_projeto' => '✏️',
                                    'atualizar_tarefa' => '📝',
                                    'excluir_projeto' => '🗑️',
                                    'excluir_tarefa' => '❌',
                                    'registro' => '👤'
                                ];
                                echo $icons[$activity['acao']] ?? '📋';
                                ?>
                            </div>
                            <div class="activity-content">
                                <p><?= htmlspecialchars($activity['descricao']) ?></p>
                                <span class="activity-time"><?= $activity['data_formatada'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <?php if (empty($recentActivity)): ?>
                        <p class="empty-message">Nenhuma atividade recente</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Estatísticas Rápidas -->
            <div class="dashboard-card">
                <div class="card-header">
                    <h2>📈 Estatísticas</h2>
                </div>
                <div class="stats-grid-mini">
                    <div class="stat-mini">
                        <span class="stat-number"><?= $statistics['projetos']['total_projetos'] ?? 0 ?></span>
                        <span class="stat-label">Projetos</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number"><?= $statistics['tarefas']['total_tarefas'] ?? 0 ?></span>
                        <span class="stat-label">Tarefas</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number"><?= $statistics['tarefas']['tarefas_concluidas'] ?? 0 ?></span>
                        <span class="stat-label">Concluídas</span>
                    </div>
                    <div class="stat-mini">
                        <span class="stat-number"><?= $statistics['tarefas']['tarefas_pendentes'] ?? 0 ?></span>
                        <span class="stat-label">Pendentes</span>
                    </div>
                </div>
                
                <!-- Distribuição por Status -->
                <div class="status-distribution">
                    <h4>Distribuição por Status</h4>
                    <?php foreach ($statistics['status_tarefas'] as $status): ?>
                        <div class="status-item">
                            <span class="status-name"><?= ucfirst($status['status']) ?></span>
                            <span class="status-count"><?= $status['quantidade'] ?></span>
                            <div class="progress-bar small">
                                <div class="progress-fill" style="width: <?= $statistics['tarefas']['total_tarefas'] > 0 ? round(($status['quantidade'] / $statistics['tarefas']['total_tarefas']) * 100) : 0 ?>%"></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="<?= BASE_URL ?>/assets/js/main.js"></script>
    <style>
        /* Estilos específicos do dashboard */
        .dashboard-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .header-actions {
            display: flex;
            gap: 10px;
        }
        .metrics-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .metric-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            align-items: center;
            gap: 15px;
        }
        .metric-icon {
            width: 50px;
            height: 50px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
        }
        .metric-info h3 {
            margin: 0 0 5px 0;
            font-size: 14px;
            color: #666;
        }
        .metric-value {
            font-size: 24px;
            font-weight: bold;
            color: #333;
        }
        .metric-info p {
            margin: 0;
            font-size: 12px;
            color: #888;
        }
        .progress-bar {
            width: 100%;
            height: 6px;
            background: #f0f0f0;
            border-radius: 3px;
            margin-top: 5px;
            overflow: hidden;
        }
        .progress-bar.small {
            height: 4px;
        }
        .progress-fill {
            height: 100%;
            background: var(--primary);
            border-radius: 3px;
            transition: width 0.3s ease;
        }
        .dashboard-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
        }
        .dashboard-card {
            background: white;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 2px 15px rgba(0,0,0,0.1);
        }
        .card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            padding-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
        }
        .card-header h2 {
            margin: 0;
            font-size: 18px;
        }
        .btn-link {
            color: var(--primary);
            text-decoration: none;
            font-size: 14px;
        }
        .btn-link:hover {
            text-decoration: underline;
        }
        .project-item {
            display: flex;
            align-items: center;
            gap: 15px;
            padding: 12px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .project-item:last-child {
            border-bottom: none;
        }
        .project-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            flex-shrink: 0;
        }
        .project-info {
            flex: 1;
        }
        .project-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .project-info p {
            margin: 0 0 5px 0;
            font-size: 12px;
            color: #666;
        }
        .project-stats {
            font-size: 12px;
            color: #888;
        }
        .deadline-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .deadline-item.urgent {
            background: #fff5f5;
            margin: 0 -15px;
            padding: 10px 15px;
            border-radius: 5px;
        }
        .task-priority {
            width: 24px;
            height: 24px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 10px;
            font-weight: bold;
            color: white;
        }
        .priority-alta { background: var(--danger); }
        .priority-media { background: var(--warning); }
        .priority-baixa { background: var(--secondary); }
        .priority-urgente { background: #e91e63; }
        .task-info {
            flex: 1;
        }
        .task-info h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .project-badge {
            padding: 2px 8px;
            border-radius: 12px;
            font-size: 11px;
            color: white;
            font-weight: 500;
        }
        .deadline-info {
            text-align: right;
        }
        .days-left {
            font-weight: bold;
            font-size: 14px;
        }
        .days-left.danger {
            color: var(--danger);
        }
        .deadline-info small {
            display: block;
            font-size: 11px;
            color: #888;
        }
        .activity-item {
            display: flex;
            align-items: flex-start;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f5f5f5;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            font-size: 16px;
            margin-top: 2px;
        }
        .activity-content p {
            margin: 0 0 5px 0;
            font-size: 14px;
        }
        .activity-time {
            font-size: 11px;
            color: #888;
        }
        .stats-grid-mini {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 15px;
            margin-bottom: 20px;
        }
        .stat-mini {
            text-align: center;
            padding: 15px;
            background: #f8f9fa;
            border-radius: 8px;
        }
        .stat-mini .stat-number {
            display: block;
            font-size: 20px;
            font-weight: bold;
            color: var(--primary);
        }
        .stat-mini .stat-label {
            font-size: 12px;
            color: #666;
        }
        .status-distribution {
            margin-top: 20px;
        }
        .status-distribution h4 {
            margin: 0 0 15px 0;
            font-size: 14px;
            color: #666;
        }
        .status-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
        }
        .status-name {
            font-size: 12px;
            color: #666;
            width: 60px;
        }
        .status-count {
            font-size: 12px;
            font-weight: bold;
            width: 30px;
        }
        .empty-message {
            text-align: center;
            color: #888;
            font-style: italic;
            padding: 20px;
            margin: 0;
        }
        @media (max-width: 1024px) {
            .dashboard-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .dashboard-header {
                flex-direction: column;
                gap: 15px;
                align-items: flex-start;
            }
            .metrics-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</body>
</html>