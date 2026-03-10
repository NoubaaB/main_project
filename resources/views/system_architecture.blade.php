<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Architecture Visualization</title>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest/dist/umd/lucide.js"></script>
    <style>
        :root {
            --bg-color: #0b1120;
            --grid-color: rgba(255, 255, 255, 0.03);
            --node-bg: rgba(30, 41, 59, 0.7);
            --node-border: rgba(148, 163, 184, 0.1);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
            --vue-color: #41b883;
            --laravel-color: #ff2d20;
            --db-color: #00758f;
            --ai-color: #a855f7;
            --accent: #38bdf8;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-main);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            background-image:
                linear-gradient(var(--grid-color) 1px, transparent 1px),
                linear-gradient(90deg, var(--grid-color) 1px, transparent 1px);
            background-size: 30px 30px;
            padding: 40px 20px;
        }

        .background-glow {
            position: fixed;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 80vw;
            height: 80vh;
            background: radial-gradient(circle, rgba(56, 189, 248, 0.05) 0%, rgba(0, 0, 0, 0) 70%);
            z-index: -1;
            pointer-events: none;
        }

        header {
            text-align: center;
            margin-bottom: 60px;
            animation: fadeInDown 1s ease-out;
            position: relative;
            z-index: 10;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 700;
            margin-bottom: 10px;
            background: linear-gradient(135deg, #fff 0%, #cbd5e1 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -0.5px;
        }

        p.subtitle {
            color: var(--text-muted);
            font-size: 1.1rem;
            max-width: 600px;
            margin: 0 auto;
        }

        .architecture-container {
            display: flex;
            flex-direction: column;
            gap: 60px;
            width: 100%;
            max-width: 1200px;
            position: relative;
            z-index: 1;
        }

        .layer {
            display: flex;
            justify-content: center;
            gap: 40px;
            flex-wrap: wrap;
            position: relative;
        }

        .layer::before {
            content: attr(data-label);
            position: absolute;
            left: 0;
            top: -30px;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 2px;
            color: var(--text-muted);
            font-weight: 600;
            opacity: 0.6;
        }

        .node {
            background: var(--node-bg);
            border: 1px solid var(--node-border);
            border-radius: 16px;
            padding: 24px;
            width: 280px;
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.1);
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            position: relative;
            z-index: 2;
            cursor: pointer;
            animation: fadeIn 0.8s ease-out backwards;
        }

        .node::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 100%;
            border-radius: 16px;
            background: linear-gradient(180deg, rgba(255, 255, 255, 0.05) 0%, transparent 100%);
            pointer-events: none;
            z-index: -1;
        }

        .node:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.3), 0 0 20px var(--glow-color, rgba(255, 255, 255, 0.1));
            border-color: rgba(255, 255, 255, 0.2);
        }

        .node-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 16px;
            padding-bottom: 16px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .icon-box {
            width: 40px;
            height: 40px;
            border-radius: 10px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: rgba(255, 255, 255, 0.05);
            box-shadow: inset 0 0 10px rgba(255, 255, 255, 0.05);
        }

        .node-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-main);
        }

        .node-subtitle {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .components-list {
            list-style: none;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }

        .component-item {
            font-size: 0.85rem;
            color: #cbd5e1;
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 6px 10px;
            background: rgba(0, 0, 0, 0.2);
            border-radius: 6px;
            border: 1px solid transparent;
            transition: all 0.2s;
        }

        .node:hover .component-item {
            border-color: rgba(255, 255, 255, 0.05);
            background: rgba(0, 0, 0, 0.3);
        }

        .component-item i {
            width: 14px;
            height: 14px;
            color: var(--accent);
        }

        /* Connecting Lines Canvas */
        #connections {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 0;
            pointer-events: none;
        }

        /* Node Specific Colors */
        .node.frontend {
            --glow-color: rgba(65, 184, 131, 0.3);
        }

        .node.frontend .icon-box {
            color: var(--vue-color);
            border: 1px solid rgba(65, 184, 131, 0.2);
        }

        .node.frontend .component-item i {
            color: var(--vue-color);
        }

        .node.backend {
            --glow-color: rgba(255, 45, 32, 0.3);
        }

        .node.backend .icon-box {
            color: var(--laravel-color);
            border: 1px solid rgba(255, 45, 32, 0.2);
        }

        .node.backend .component-item i {
            color: var(--laravel-color);
        }

        .node.database {
            --glow-color: rgba(0, 117, 143, 0.3);
        }

        .node.database .icon-box {
            color: var(--db-color);
            border: 1px solid rgba(0, 117, 143, 0.2);
        }

        .node.database .component-item i {
            color: var(--db-color);
        }

        .node.ai {
            --glow-color: rgba(168, 85, 247, 0.3);
        }

        .node.ai .icon-box {
            color: var(--ai-color);
            border: 1px solid rgba(168, 85, 247, 0.2);
        }

        .node.ai .component-item i {
            color: var(--ai-color);
        }

        .node.model {
            --glow-color: rgba(234, 179, 8, 0.3);
            width: 240px;
        }

        .node.model .icon-box {
            color: #eab308;
            border: 1px solid rgba(234, 179, 8, 0.2);
        }

        .node.model .component-item i {
            color: #eab308;
        }

        .node-mini {
            padding: 16px;
            width: 180px;
        }

        .node-mini .node-header {
            margin-bottom: 4px;
            padding-bottom: 4px;
            border-bottom: none;
        }

        /* Animations */
        @keyframes fadeInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: scale(0.95) translateY(10px);
            }

            to {
                opacity: 1;
                transform: scale(1) translateY(0);
            }
        }

        .layer:nth-child(1) .node {
            animation-delay: 0.1s;
        }

        .layer:nth-child(2) .node {
            animation-delay: 0.3s;
        }

        .layer:nth-child(3) .node {
            animation-delay: 0.5s;
        }

        .pulse-dot {
            position: absolute;
            width: 8px;
            height: 8px;
            background: var(--accent);
            border-radius: 50%;
            box-shadow: 0 0 10px var(--accent), 0 0 20px var(--accent);
            z-index: 5;
            pointer-events: none;
        }

        /* Tooltip */
        .tooltip {
            position: absolute;
            background: rgba(15, 23, 42, 0.95);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 12px 16px;
            border-radius: 8px;
            font-size: 0.85rem;
            color: #e2e8f0;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            z-index: 100;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.5);
            max-width: 250px;
            backdrop-filter: blur(4px);
        }

        .tooltip.visible {
            opacity: 1;
        }

        .tooltip-title {
            font-weight: 600;
            color: #fff;
            margin-bottom: 4px;
            display: block;
        }

        /* Modal Styles */
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            display: flex;
            justify-content: center;
            align-items: center;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s;
        }

        .modal-overlay.active {
            opacity: 1;
            pointer-events: all;
        }

        .modal-content {
            background: var(--node-bg);
            border: 1px solid var(--node-border);
            border-radius: 16px;
            width: 90%;
            max-width: 800px;
            max-height: 80vh;
            overflow-y: auto;
            padding: 30px;
            position: relative;
            transform: translateY(20px);
            transition: transform 0.3s;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(12px);
        }

        .modal-overlay.active .modal-content {
            transform: translateY(0);
        }

        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: transparent;
            border: none;
            color: var(--text-muted);
            cursor: pointer;
            transition: color 0.2s;
        }

        .modal-close:hover {
            color: #fff;
        }

        .modal-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .detail-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .detail-table th,
        .detail-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        .detail-table th {
            color: var(--accent);
            font-weight: 500;
        }

        .detail-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .tag {
            background: rgba(56, 189, 248, 0.1);
            color: var(--accent);
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.8rem;
            margin: 2px;
            display: inline-block;
        }

        .tag.pk {
            color: var(--vue-color);
            background: rgba(65, 184, 131, 0.1);
        }

        .tag.fk {
            color: var(--eab308);
            background: rgba(234, 179, 8, 0.1);
        }

        /* Custom scrollbar for modal */
        .modal-content::-webkit-scrollbar {
            width: 8px;
        }

        .modal-content::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.2);
            border-radius: 4px;
        }

        .modal-content::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }
    /* Split View for Large Data */
        .split-view {
            display: flex;
            height: 60vh;
            gap: 24px;
        }

        .split-sidebar {
            flex: 0 0 250px;
            display: flex;
            flex-direction: column;
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            padding-right: 16px;
        }

        .split-main {
            flex: 1;
            overflow-y: auto;
            padding-right: 10px;
        }

        .search-box {
            position: relative;
            margin-bottom: 16px;
        }

        .search-box input {
            width: 100%;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-main);
            padding: 10px 10px 10px 36px;
            border-radius: 8px;
            font-family: 'Outfit', sans-serif;
            outline: none;
            transition: border-color 0.2s;
        }
        
        .search-box input:focus {
            border-color: var(--accent);
        }

        .search-box i {
            position: absolute;
            left: 10px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            width: 16px;
            height: 16px;
        }

        .table-list {
            flex: 1;
            overflow-y: auto;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .table-list::-webkit-scrollbar {
            width: 4px;
        }
        .table-list::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .split-main::-webkit-scrollbar {
            width: 6px;
        }
        .split-main::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.1);
            border-radius: 4px;
        }

        .table-item {
            padding: 10px 12px;
            border-radius: 6px;
            cursor: pointer;
            color: var(--text-muted);
            font-size: 0.95rem;
            transition: all 0.2s;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .table-item:hover {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-main);
        }

        .table-item.active {
            background: rgba(56, 189, 248, 0.15);
            color: #fff;
            border-left: 3px solid var(--accent);
        }

        /* Better column grid */
        .column-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
            gap: 12px;
            margin-top: 16px;
        }

        .column-card {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 8px;
            padding: 12px;
            transition: border-color 0.2s;
        }
        
        .column-card:hover {
            border-color: rgba(255, 255, 255, 0.15);
        }

        .column-card-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
        }

        .column-name {
            font-weight: 500;
            color: var(--text-main);
            word-break: break-all;
        }

        .relationships-box {
            background: rgba(168, 85, 247, 0.1);
            border: 1px solid rgba(168, 85, 247, 0.2);
            border-radius: 8px;
            padding: 16px;
            margin-top: 24px;
        }
        
        .relationships-box.standard {
            background: rgba(255, 255, 255, 0.02);
            border-color: rgba(255, 255, 255, 0.05);
        }

        .rel-title {
            color: var(--ai-color);
            font-size: 0.9rem;
            font-weight: 600;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        
        .rel-title.standard {
            color: var(--text-muted);
        }
    </style>
</head>

<body>

    <div class="background-glow"></div>
    <div class="tooltip" id="tooltip"></div>

    <header>
        <h1>System Architecture</h1>
        <p class="subtitle">Interactive overview of the Vue.js Frontend, Laravel Backend, and AI Integration.</p>
    </header>

    <div class="architecture-container" id="container">
        <canvas id="connections"></canvas>

        <!-- Presentation Layer -->
        <div class="layer" data-label="Presentation / Client Layer">
            <div class="node frontend" id="node-spa"
                data-info="The main Single Page Application built with Vue.js 3, handling user interactions and dynamic rendering.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="layout-template"></i></div>
                    <div>
                        <div class="node-title">Vue.js SPA</div>
                        <div class="node-subtitle">User Interface (Vite)</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="bot"></i> AiAssistant.vue</li>
                    <li class="component-item"><i data-lucide="table-2"></i> DataTable.vue</li>
                    <li class="component-item"><i data-lucide="plus-circle"></i> AddCharge/index.vue</li>
                    <li class="component-item"><i data-lucide="list"></i> ListCharge</li>
                </ul>
            </div>

            <div class="node frontend" id="node-store"
                data-info="Pinia state management store, caching data globally for the frontend to minimize redundant API calls.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="database-zap"></i></div>
                    <div>
                        <div class="node-title">State Management</div>
                        <div class="node-subtitle">Pinia Store</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="box"></i> stores/Charge.js</li>
                    <li class="component-item"><i data-lucide="refresh-cw"></i> Reactive State</li>
                    <li class="component-item"><i data-lucide="zap"></i> Actions & Getters</li>
                </ul>
            </div>
        </div>

        <!-- Application Layer -->
        <div class="layer" data-label="Application / API Layer">
            <div class="node backend" id="node-api"
                data-info="Laravel RESTful API handling business logic, authentication, and routing requests to appropriate controllers.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="server"></i></div>
                    <div>
                        <div class="node-title">Laravel API Base</div>
                        <div class="node-subtitle">Routing & Middleware</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="route"></i> api.php Routes</li>
                    <li class="component-item"><i data-lucide="shield-check"></i> Auth Middleware</li>
                    <li class="component-item"><i data-lucide="file-json"></i> JSON Responses</li>
                </ul>
            </div>

            <div class="node backend" id="node-controllers"
                data-info="Controllers and Services processing requests. The AIQueryController interprets user prompts using LLMs.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="cpu"></i></div>
                    <div>
                        <div class="node-title">Controllers & Services</div>
                        <div class="node-subtitle">Business Logic</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="brain-circuit"></i> AIQueryController.php</li>
                    <li class="component-item"><i data-lucide="briefcase"></i> ChargeController.php</li>
                    <li class="component-item"><i data-lucide="wrench"></i> CustomService.php</li>
                </ul>
            </div>

            <div class="node ai" id="node-ai-service"
                data-info="External Large Language Model API (e.g., OpenAI) used to parse unstructured user queries into structured SQL or charts.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="sparkles"></i></div>
                    <div>
                        <div class="node-title">LLM AI Engine</div>
                        <div class="node-subtitle">External Service</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="message-square"></i> Prompt Processing</li>
                    <li class="component-item"><i data-lucide="code-2"></i> Query/Component Gen</li>
                </ul>
            </div>
        </div>

        <!-- Data Layer -->
        <div class="layer" data-label="Data Layer">
            <div class="node database" id="node-db"
                data-info="Main relational database storing application entities like Charges, Users, and cached AI queries.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="database"></i></div>
                    <div>
                        <div class="node-title">MySQL Database</div>
                        <div class="node-subtitle">Persistent Storage</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- ORM Layer -->
        <div class="layer" data-label="ORM Models & Relationships">
            <div class="node model node-mini" id="model-supplier"
                data-info="Supplier entity providing fish or other items.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="truck"></i></div>
                    <div>
                        <div class="node-title">Supplier</div>
                    </div>
                </div>
            </div>

            <div class="node model" id="model-charge"
                data-info="Charge model representing incoming inventory/purchases. Has many Suppliers, Ships, and operations.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="package-plus"></i></div>
                    <div>
                        <div class="node-title">Charge</div>
                        <div class="node-subtitle">Incoming</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="link"></i> belongsToMany(Supplier)</li>
                    <li class="component-item"><i data-lucide="link"></i> belongsToMany(Ship)</li>
                    <li class="component-item"><i data-lucide="link"></i> morphMany(Operations)</li>
                </ul>
            </div>

            <div class="node model node-mini" id="model-ship"
                data-info="Ship entity related to both Charges and Sells.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="ship"></i></div>
                    <div>
                        <div class="node-title">Ship</div>
                    </div>
                </div>
            </div>

            <div class="node model" id="model-sell"
                data-info="Sell model representing outgoing items/sales. Has many Clients, Ships.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="shopping-cart"></i></div>
                    <div>
                        <div class="node-title">Sell</div>
                        <div class="node-subtitle">Outgoing</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="link"></i> belongsToMany(Client)</li>
                    <li class="component-item"><i data-lucide="link"></i> belongsToMany(Ship)</li>
                    <li class="component-item"><i data-lucide="link"></i> morphMany(Operations)</li>
                </ul>
            </div>

            <div class="node model node-mini" id="model-client" data-info="Client entity purchasing items.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="users"></i></div>
                    <div>
                        <div class="node-title">Client</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="layer" style="margin-top: -20px;">
            <div class="node model" id="model-warehouse"
                data-info="Warehouse stockable representing inventory items linked to operations from Sell/Charge via polymorphism.">
                <div class="node-header">
                    <div class="icon-box"><i data-lucide="warehouse"></i></div>
                    <div>
                        <div class="node-title">WarehouseStockable</div>
                        <div class="node-subtitle">Inventory Management</div>
                    </div>
                </div>
                <ul class="components-list">
                    <li class="component-item"><i data-lucide="link"></i> morphTo(Stockable)</li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Modal overlay for node details -->
    <div class="modal-overlay" id="modal">
        <div class="modal-content">
            <button class="modal-close" onclick="closeModal()"><i data-lucide="x"></i></button>
            <h2 class="modal-title" id="modal-title">Details</h2>
            <div id="modal-body">
                <!-- Dynamic Content Will Be Injected Here via JavaScript -->
            </div>
        </div>
    </div>

    <script>
        // Initialize Lucide Icons
        lucide.createIcons();

        // Canvas Connections Drawing
        const canvas = document.getElementById('connections');
        const ctx = canvas.getContext('2d');
        const container = document.getElementById('container');

        // Define edges (connections between nodes)
        const edges = [
            { from: 'node-spa', to: 'node-store', color: 'rgba(65, 184, 131, 0.4)' },
            { from: 'node-spa', to: 'node-api', color: 'rgba(255, 255, 255, 0.2)', animated: true },
            { from: 'node-store', to: 'node-api', color: 'rgba(255, 255, 255, 0.2)' },
            { from: 'node-api', to: 'node-controllers', color: 'rgba(255, 45, 32, 0.4)' },
            { from: 'node-controllers', to: 'node-db', color: 'rgba(0, 117, 143, 0.4)' },
            { from: 'node-controllers', to: 'node-ai-service', color: 'rgba(168, 85, 247, 0.4)', animated: true, reverse: true },
            { from: 'node-db', to: 'model-charge', color: 'rgba(255, 255, 255, 0.15)', dashed: true },
            { from: 'node-db', to: 'model-sell', color: 'rgba(255, 255, 255, 0.15)', dashed: true },
            { from: 'model-sell', to: 'model-client', color: 'rgba(234, 179, 8, 0.4)' },
            { from: 'model-charge', to: 'model-supplier', color: 'rgba(234, 179, 8, 0.4)' },
            { from: 'model-charge', to: 'model-ship', color: 'rgba(234, 179, 8, 0.4)' },
            { from: 'model-sell', to: 'model-ship', color: 'rgba(234, 179, 8, 0.4)' },
            { from: 'model-charge', to: 'model-warehouse', color: 'rgba(234, 179, 8, 0.4)' },
            { from: 'model-sell', to: 'model-warehouse', color: 'rgba(234, 179, 8, 0.4)' }
        ];

        function resizeCanvas() {
            canvas.width = container.offsetWidth;
            canvas.height = container.offsetHeight;
            drawConnections();
        }

        function drawConnections() {
            ctx.clearRect(0, 0, canvas.width, canvas.height);

            edges.forEach(edge => {
                const elFrom = document.getElementById(edge.from);
                const elTo = document.getElementById(edge.to);

                if (!elFrom || !elTo) return;

                const rectFrom = elFrom.getBoundingClientRect();
                const rectTo = elTo.getBoundingClientRect();
                const containerRect = container.getBoundingClientRect();

                // Calculate center points relative to container
                const fromX = rectFrom.left + rectFrom.width / 2 - containerRect.left;
                const fromY = rectFrom.top + rectFrom.height / 2 - containerRect.top;
                const toX = rectTo.left + rectTo.width / 2 - containerRect.left;
                const toY = rectTo.top + rectTo.height / 2 - containerRect.top;

                // Draw curve
                ctx.beginPath();
                ctx.moveTo(fromX, fromY);

                // Vertical curve
                const cpY = fromY + (toY - fromY) / 2;
                ctx.bezierCurveTo(fromX, cpY, toX, cpY, toX, toY);

                ctx.strokeStyle = edge.color;
                ctx.lineWidth = 2;
                ctx.shadowBlur = 5;
                ctx.shadowColor = edge.color;

                // If it's the animated AI connection, make it dashed
                if (edge.animated) {
                    ctx.setLineDash([8, 8]);
                    ctx.lineDashOffset = -Date.now() / 20; // Animate dash
                } else if (edge.dashed) {
                    ctx.setLineDash([4, 4]);
                } else {
                    ctx.setLineDash([]);
                }

                ctx.stroke();
                ctx.shadowBlur = 0; // reset
            });

            requestAnimationFrame(drawConnections);
        }

        window.addEventListener('resize', resizeCanvas);

        // Initial setup
        setTimeout(() => {
            resizeCanvas();
        }, 800); // Wait for nodes to animate in

        // Tooltip logic
        const tooltip = document.getElementById('tooltip');
        const nodes = document.querySelectorAll('.node');

        nodes.forEach(node => {
            node.addEventListener('mouseenter', (e) => {
                const title = node.querySelector('.node-title').textContent;
                const info = node.getAttribute('data-info');

                tooltip.innerHTML = `<span class="tooltip-title">${title}</span>${info}`;
                tooltip.classList.add('visible');
            });

            node.addEventListener('mousemove', (e) => {
                tooltip.style.left = e.pageX + 15 + 'px';
                tooltip.style.top = e.pageY + 15 + 'px';
            });

            node.addEventListener('mouseleave', () => {
                tooltip.classList.remove('visible');
            });

            // Allow click to open Modal
            node.addEventListener('click', () => {
                openModal(node.id);
            });
        });

        // Modal Logic
        const nodeDetails = {
            'node-spa': {
                title: 'Vue.js SPA',
                type: 'frontend',
                files: [
                    { name: 'AiAssistant.vue', description: 'Handles the chat interface, sending prompts to AI API and dynamically rendering response components.', reason: 'Provides seamless, conversational UX for retrieving and analyzing data.' },
                    { name: 'DataTable.vue', description: 'Reusable data grid handling sorting, filtering, and dynamic column generation.', reason: 'Centralizes and standardizes lists presentation across modules.' },
                    { name: 'AddCharge/index.vue', description: 'Form UI for inserting new charges.', reason: 'Separates creation concerns from list views.' },
                    { name: 'ListCharge/index.vue', description: 'Wrapper utilizing the DataTable for displaying the list of recorded charges.', reason: 'Main entry point for users acting on Charges.' }
                ]
            },
            'node-store': {
                title: 'State Management (Pinia)',
                type: 'frontend',
                files: [
                    { name: 'stores/Charge.js', description: 'Holds reactive global state, actions for API requests, and computed getters for Charges.', reason: 'Prevents prop-drilling, caching the current dataset to improve app speed.' }
                ]
            },
            'node-api': {
                title: 'Laravel API Base',
                type: 'backend',
                tables: [
                    { name: 'users', columns: [{ name: 'id', type: 'pk' }, { name: 'name', type: 'string' }, { name: 'email', type: 'string' }, { name: 'password', type: 'string' }], relationships: 'HasMany(ai_queries)' },
                    { name: 'personal_access_tokens', columns: [{ name: 'id', type: 'pk' }, { name: 'tokenable_type', type: 'string' }, { name: 'tokenable_id', type: 'fk' }, { name: 'token', type: 'string' }], relationships: 'MorphTo(User)' }
                ]
            },
            'node-controllers': {
                title: 'Controllers & Services',
                type: 'backend',
                tables: [
                    { name: 'ai_queries', columns: [{ name: 'id', type: 'pk' }, { name: 'user_id', type: 'fk' }, { name: 'prompt', type: 'text' }, { name: 'response', type: 'json' }], relationships: 'BelongsTo(User)' }
                ]
            },
            'node-ai-service': {
                title: 'External LLM AI Engine Details',
                type: 'backend',
                tables: [
                    { name: 'N/A (External API)', columns: [{ name: 'prompt', type: 'text' }, { name: 'schema', type: 'json' }], relationships: 'Interacts with AIQueryController to interpret instructions into specific JSON formatted structures' }
                ]
            },
            'node-db': {
                title: 'Core Database Tables',
                type: 'backend',
                tables: [
                    {
                        "name": "cache",
                        "columns": [
                            {
                                "name": "key",
                                "type": "pk"
                            },
                            {
                                "name": "value",
                                "type": "mediumtext"
                            },
                            {
                                "name": "expiration",
                                "type": "int(11)"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "cache_locks",
                        "columns": [
                            {
                                "name": "key",
                                "type": "pk"
                            },
                            {
                                "name": "owner",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "expiration",
                                "type": "int(11)"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "charge_categories",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "enable_tracking_numbers",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "enable_tracking_onp_numbers",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "enable_fishery_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_origin_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_supplier_process_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "charges(): hasMany"
                    },
                    {
                        "name": "charge_ships",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "charge_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "charge_suppliers",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "charge_id",
                                "type": "fk"
                            },
                            {
                                "name": "supplier_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "charges",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "tracking_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "tracking_onp_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "note",
                                "type": "varchar(500)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "allow_invoice",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "invoice_id",
                                "type": "fk"
                            },
                            {
                                "name": "fishery_id",
                                "type": "fk"
                            },
                            {
                                "name": "ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "origin_id",
                                "type": "fk"
                            },
                            {
                                "name": "supplier_process_id",
                                "type": "fk"
                            },
                            {
                                "name": "charge_category_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "non_warehouse_stockable_operations(): hasMany, charge_category(): belongsTo, warehouse_stockable_tracking_number_operations(): morphMany, fishery(): belongsTo, trucks(): belongsTo, origin(): belongsTo, supplier_process(): belongsTo, supplier_invoice(): belongsTo"
                    },
                    {
                        "name": "clients",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "phone",
                                "type": "varchar(20)"
                            },
                            {
                                "name": "ice",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "email",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "address",
                                "type": "varchar(100)"
                            },
                            {
                                "name": "city",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "country",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "postal_code",
                                "type": "varchar(10)"
                            },
                            {
                                "name": "website",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "logo_image",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "active_status",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "fish_families",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "fish_types(): hasMany"
                    },
                    {
                        "name": "fish_process_types",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "fish_types(): hasMany"
                    },
                    {
                        "name": "fish_types",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_in_invoice",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "scientific_name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "image",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "process",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "fish_family_id",
                                "type": "fk"
                            },
                            {
                                "name": "fish_process_type_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "fish_family(): belongsTo, fish_process_type(): belongsTo"
                    },
                    {
                        "name": "fisheries",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "fixed_charges",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "start_date",
                                "type": "date"
                            },
                            {
                                "name": "end_date",
                                "type": "date"
                            },
                            {
                                "name": "tracking_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "name",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(500)"
                            },
                            {
                                "name": "amount",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "fixed_periodic_charge",
                                "type": "enum('daily','weekly','monthly','yearly')"
                            },
                            {
                                "name": "charge_category_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "type_charge(): belongsTo"
                    },
                    {
                        "name": "formulas",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "text"
                            },
                            {
                                "name": "expressions",
                                "type": "longtext"
                            },
                            {
                                "name": "enabled",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "type",
                                "type": "enum('charge','sell')"
                            },
                            {
                                "name": "created_by",
                                "type": "bigint(20) unsigned"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "guano_types",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_in_invoice",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "scientific_name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "image",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "process",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "job_batches",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "total_jobs",
                                "type": "int(11)"
                            },
                            {
                                "name": "pending_jobs",
                                "type": "int(11)"
                            },
                            {
                                "name": "failed_jobs",
                                "type": "int(11)"
                            },
                            {
                                "name": "failed_job_ids",
                                "type": "fk"
                            },
                            {
                                "name": "options",
                                "type": "mediumtext"
                            },
                            {
                                "name": "cancelled_at",
                                "type": "int(11)"
                            },
                            {
                                "name": "created_at",
                                "type": "int(11)"
                            },
                            {
                                "name": "finished_at",
                                "type": "int(11)"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "jobs",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "queue",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "payload",
                                "type": "longtext"
                            },
                            {
                                "name": "attempts",
                                "type": "tinyint(3) unsigned"
                            },
                            {
                                "name": "reserved_at",
                                "type": "int(10) unsigned"
                            },
                            {
                                "name": "available_at",
                                "type": "int(10) unsigned"
                            },
                            {
                                "name": "created_at",
                                "type": "int(10) unsigned"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "non_warehouse_stockable_operations",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "unit_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight_bill",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "charge_id",
                                "type": "fk"
                            },
                            {
                                "name": "package_type_id",
                                "type": "fk"
                            },
                            {
                                "name": "warehouse_category_operation_id",
                                "type": "fk"
                            },
                            {
                                "name": "stockable_type",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "stockable_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "charge(): belongsTo, stockable(): morphTo, package_type(): belongsTo"
                    },
                    {
                        "name": "origins",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "charges(): hasMany"
                    },
                    {
                        "name": "package_types",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "size",
                                "type": "int(11)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "type_emballage",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse_stockable_tracking_number_operations(): morphMany"
                    },
                    {
                        "name": "password_reset_tokens",
                        "columns": [
                            {
                                "name": "email",
                                "type": "pk"
                            },
                            {
                                "name": "token",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "profit_and_loss_accounts",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "fiscal_year",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "period_label",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "sales_revenue",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "other_income",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_revenue",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "cost_of_goods_sold",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "operating_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "administrative_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "marketing_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "financial_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "tax_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_expenses",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "gross_profit",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "net_profit",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "accountable_type",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "accountable_id",
                                "type": "fk"
                            },
                            {
                                "name": "notes",
                                "type": "text"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "accountable(): morphTo"
                    },
                    {
                        "name": "roles",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "sell_categories",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "enable_tracking_numbers",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "enable_tracking_onp_numbers",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "enable_fishery_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_origin_id",
                                "type": "fk"
                            },
                            {
                                "name": "enable_supplier_process_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "sell_clients",
                        "columns": [
                            {
                                "name": "sell_id",
                                "type": "fk"
                            },
                            {
                                "name": "client_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "sell_ships",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "sell_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "sells",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "tracking_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "note",
                                "type": "varchar(500)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "fishery_id",
                                "type": "fk"
                            },
                            {
                                "name": "ship_id",
                                "type": "fk"
                            },
                            {
                                "name": "origin_id",
                                "type": "fk"
                            },
                            {
                                "name": "sell_category_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "sessions",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "user_id",
                                "type": "fk"
                            },
                            {
                                "name": "ip_address",
                                "type": "varchar(45)"
                            },
                            {
                                "name": "user_agent",
                                "type": "text"
                            },
                            {
                                "name": "payload",
                                "type": "longtext"
                            },
                            {
                                "name": "last_activity",
                                "type": "int(11)"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "ships",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "imo_number",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "supplier_invoices",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "type_invoice",
                                "type": "enum('local','export')"
                            },
                            {
                                "name": "tracking_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "supplier_ids",
                                "type": "fk"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "print_counter",
                                "type": "int(11)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "charges(): hasMany"
                    },
                    {
                        "name": "suppliers",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "phone",
                                "type": "varchar(20)"
                            },
                            {
                                "name": "ice",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "email",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "address",
                                "type": "varchar(100)"
                            },
                            {
                                "name": "city",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "country",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "postal_code",
                                "type": "varchar(10)"
                            },
                            {
                                "name": "website",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "logo_image",
                                "type": "varchar(200)"
                            },
                            {
                                "name": "active_status",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "truck_charge",
                        "columns": [
                            {
                                "name": "truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "charge_id",
                                "type": "fk"
                            }
                        ],
                        "relationships": "Pivot for truck and charge"
                    },
                    {
                        "name": "truck_non_warehouse_stockable_operation",
                        "columns": [
                            {
                                "name": "truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "non_warehouse_stockable_operation_id",
                                "type": "fk"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "truck_sell",
                        "columns": [
                            {
                                "name": "truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "sell_id",
                                "type": "fk"
                            }
                        ],
                        "relationships": "Pivot for truck and sell"
                    },
                    {
                        "name": "truck_warehouse_stockable_tracking_number_operation",
                        "columns": [
                            {
                                "name": "truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "warehouse_stockable_tracking_number_operation_id",
                                "type": "fk"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "trucks",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "registration_plate",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "charges(): belongsTo, sells(): belongsTo"
                    },
                    {
                        "name": "users",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "email",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "email_verified_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "password",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "role_id",
                                "type": "fk"
                            },
                            {
                                "name": "remember_token",
                                "type": "varchar(100)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "Standard Table"
                    },
                    {
                        "name": "warehouse_categories",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(250)"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse(): hasMany"
                    },
                    {
                        "name": "warehouse_category_operations",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name_fr",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_en",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "name_es",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "allow_weight",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "allow_price",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "allow_package_type",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "allow_warehouse",
                                "type": "tinyint(1)"
                            },
                            {
                                "name": "allow_detail_truck_id",
                                "type": "fk"
                            },
                            {
                                "name": "operation_type",
                                "type": "enum('charge','sells','both')"
                            },
                            {
                                "name": "warehouse_type",
                                "type": "enum('FishType','GuanoType','PackageType')"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse_stockable_tracking_number_operations(): hasMany, non_warehouse_stockable_operations(): hasMany"
                    },
                    {
                        "name": "warehouse_stockable_tracking_number_operations",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "type_operation",
                                "type": "enum('in','out')"
                            },
                            {
                                "name": "date",
                                "type": "date"
                            },
                            {
                                "name": "unit_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight_bill",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "package_type_id",
                                "type": "fk"
                            },
                            {
                                "name": "warehouse_stockable_tracking_number_id",
                                "type": "fk"
                            },
                            {
                                "name": "warehouse_category_operation_id",
                                "type": "fk"
                            },
                            {
                                "name": "stockable_type",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "stockable_id",
                                "type": "fk"
                            },
                            {
                                "name": "sourceable_type",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "sourceable_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse_stockable_tracking_number(): belongsTo, sourceable(): morphTo, stockable(): morphTo, package_type(): belongsTo, warehouse_category_operation(): belongsTo"
                    },
                    {
                        "name": "warehouse_stockable_tracking_numbers",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "tracking_numbers",
                                "type": "longtext"
                            },
                            {
                                "name": "date_in_warehouse",
                                "type": "date"
                            },
                            {
                                "name": "date_out_of_warehouse",
                                "type": "date"
                            },
                            {
                                "name": "unit_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "warehouse_stockable_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse_stockable(): belongsTo, warehouse_stockable_tracking_number_operations(): hasMany"
                    },
                    {
                        "name": "warehouse_stockables",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "unit_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "warehouse_id",
                                "type": "fk"
                            },
                            {
                                "name": "stockable_type",
                                "type": "varchar(255)"
                            },
                            {
                                "name": "stockable_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse(): belongsTo, warehouse_stockable_tracking_numbers(): hasMany, stockable(): morphTo"
                    },
                    {
                        "name": "warehouses",
                        "columns": [
                            {
                                "name": "id",
                                "type": "pk"
                            },
                            {
                                "name": "name",
                                "type": "varchar(50)"
                            },
                            {
                                "name": "description",
                                "type": "varchar(250)"
                            },
                            {
                                "name": "unit_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_weight",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_price",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "total_package_count",
                                "type": "decimal(15,2)"
                            },
                            {
                                "name": "warehouse_type",
                                "type": "enum('FishType','GuanoType','PackageType')"
                            },
                            {
                                "name": "warehouse_category_id",
                                "type": "fk"
                            },
                            {
                                "name": "created_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "updated_at",
                                "type": "timestamp"
                            },
                            {
                                "name": "deleted_at",
                                "type": "timestamp"
                            }
                        ],
                        "relationships": "warehouse_stockables(): hasMany, warehouse_category(): belongsTo"
                    }
                ]
            },
            'model-charge': {
                title: 'Charge Model',
                type: 'model',
                structure: 'Represents incoming inventory. Uses SoftDeletes. Protected properties define arrays and relations. Uses eloquent dynamic accessor Appends (supplier_ids).',
                functions: [
                    { name: 'calc()', description: 'Calculates the sum of total_weight, total_price, and total_package_count from associated polymorphic warehouse operations.' },
                    { name: 'suppliers()', description: 'Returns BelongsToMany relation linking to Supplier using pivot table `charge_suppliers`.' },
                    { name: 'ships()', description: 'Returns BelongsToMany relation linking to Ship using pivot table `charge_ships`.' },
                    { name: 'warehouse_stockable_tracking_number_operations()', description: 'Returns MorphMany relation to fetch generic polymorphic tracking operations attached.' }
                ]
            },
            'model-sell': {
                title: 'Sell Model',
                type: 'model',
                structure: 'Represents outgoing sales. Uses SoftDeletes. Similar traits and properties to Charge, managing dynamic arrays for data loading via API.',
                functions: [
                    { name: 'calc()', description: 'Calculates totals dynamically based on associated polymorphic warehouse stockable tracking number operations.' },
                    { name: 'clients()', description: 'Returns BelongsToMany relation to the Client model tracking purchasers via pivot `sell_clients`.' },
                    { name: 'ships()', description: 'Returns BelongsToMany relation linking to Ship model handled in sales.' },
                    { name: 'warehouse_stockable_tracking_number_operations()', description: 'Returns MorphMany linkage polymorphic operations similarly to charges.' }
                ]
            },
            'model-warehouse': {
                title: 'WarehouseStockable Model',
                type: 'model',
                structure: 'MorphTo based Stock entity linking physical/abstract inventory properties directly either to a Sale entity or Charge Entity.',
                functions: [
                    { name: 'calc()', description: 'Iterates related `warehouse_stockable_tracking_numbers` models and aggregates totals weight, pricing, and package counts.' },
                    { name: 'warehouse()', description: 'BelongsTo link defining which physical structural warehouse hosts the element.' },
                    { name: 'stockable()', description: 'Polymorphic MorphTo defining the generic tie back to its creator parent model.' }
                ]
            },
            'model-supplier': {
                title: 'Supplier Model',
                type: 'model',
                structure: 'Basic Provider generic model.',
                functions: [
                    { name: 'charges()', description: 'Reverse inverse relation yielding related charges BelongsToMany.' }
                ]
            },
            'model-ship': {
                title: 'Ship Model',
                type: 'model',
                structure: 'Vessel Transport generic model cross-linked between entities.',
                functions: [
                    { name: 'charges()', description: 'Fetches tied Charges.' },
                    { name: 'sells()', description: 'Fetches tied Sells.' }
                ]
            },
            'model-client': {
                title: 'Client Model',
                type: 'model',
                structure: 'Consumer generic model definitions.',
                functions: [
                    { name: 'sells()', description: 'Fetches associated Sells.' }
                ]
            }
        };

        function formatColumns(cols) {
            return cols.map(c => {
                let cl = 'tag';
                if (c.type === 'pk') cl += ' pk';
                if (c.type === 'fk') cl += ' fk';
                return `<span class="${cl}">${c.name} (${c.type})</span>`;
            }).join('');
        }

        let activeNodeData = null;

        function openModal(nodeId) {
            const data = nodeDetails[nodeId];
            if (!data) return;

            activeNodeData = data;
            let content = '';

            if (data.type === 'frontend') {
                content += `
                    <table class="detail-table">
                        <thead><tr><th>Component / File</th><th style="width: 40%">Description</th><th>Reason / Purpose</th></tr></thead>
                        <tbody>
                            ${data.files.map(f => `
                                <tr>
                                    <td><span class="tag">${f.name}</span></td>
                                    <td>${f.description}</td>
                                    <td style="color: var(--text-muted)">${f.reason}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>`;
            } else if (data.type === 'backend' && data.tables && data.tables.length > 5) {
                // Split View for Large Data (like Core DB)
                content += `
                    <div class="split-view">
                        <div class="split-sidebar">
                            <div class="search-box">
                                <i data-lucide="search"></i>
                                <input type="text" id="tableSearch" placeholder="Search tables..." onkeyup="filterTables()">
                            </div>
                            <div class="table-list" id="tableList">
                                ${data.tables.map((t, idx) => `
                                    <div class="table-item ${idx === 0 ? 'active' : ''}" onclick="selectTableCell(${idx}, this)" data-name="${t.name}">
                                        <i data-lucide="table"></i> ${t.name}
                                    </div>
                                `).join('')}
                            </div>
                        </div>
                        <div class="split-main" id="splitMainContent">
                            ${renderTableDetail(data.tables[0])}
                        </div>
                    </div>
                `;
            } else if (data.type === 'backend') {
                content += `
                    <table class="detail-table">
                        <thead><tr><th style="width: 25%">Table</th><th style="width: 45%">Columns & DataType</th><th>Relationships</th></tr></thead>
                        <tbody>
                            ${data.tables.map(t => `
                                <tr>
                                    <td><span style="color:var(--text-main); font-weight: 500">${t.name}</span></td>
                                    <td>${formatColumns(t.columns)}</td>
                                    <td style="color: var(--text-muted)">${t.relationships}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>`;
            } else if (data.type === 'model') {
                content += `
                    <div style="margin-bottom: 24px; color: var(--text-muted); line-height: 1.6;">
                        <strong style="color: var(--text-main);">Model Architecture:</strong> ${data.structure}
                    </div>
                    <table class="detail-table">
                        <thead><tr><th style="width: 45%">Function / Method</th><th>Description / Return Type</th></tr></thead>
                        <tbody>
                            ${data.functions.map(fn => `
                                <tr>
                                    <td><span class="tag" style="background: rgba(234, 179, 8, 0.1); color: #eab308;">${fn.name}</span></td>
                                    <td style="color: var(--text-muted)">${fn.description}</td>
                                </tr>
                            `).join('')}
                        </tbody>
                    </table>`;
            }

            document.getElementById('modal-title').innerText = data.title;
            document.getElementById('modal-body').innerHTML = content;
            document.getElementById('modal').classList.add('active');

            // Re-render lucide icons if added dynamically
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function renderTableDetail(table) {
            const hasRels = table.relationships && table.relationships !== 'Standard Table';
            const relBoxClass = hasRels ? '' : 'standard';
            const relTitleClass = hasRels ? '' : 'standard';
            const relIcon = hasRels ? 'link-2' : 'info';
            
            return `
                <h3 style="color: var(--accent); margin-bottom: 8px; font-size: 1.3rem; display: flex; align-items: center; gap: 8px;">
                    <i data-lucide="database"></i> ${table.name}
                </h3>
                <p style="color: var(--text-muted); font-size: 0.9rem; margin-bottom: 20px;">
                    Contains ${table.columns.length} columns.
                </p>
                
                <div class="relationships-box ${relBoxClass}">
                    <div class="rel-title ${relTitleClass}"><i data-lucide="${relIcon}"></i> Relationships (Eloquent ORM)</div>
                    <div style="color: var(--text-main); font-size: 0.95rem; line-height: 1.5;">
                        ${table.relationships}
                    </div>
                </div>

                <h4 style="margin-top: 24px; color: #fff; font-weight: 500; display: flex; align-items: center; gap: 6px;">
                    <i data-lucide="columns" style="width: 18px; color: var(--text-muted)"></i> Schema Columns
                </h4>
                <div class="column-grid">
                    ${table.columns.map(c => {
                        let badge = '';
                        if (c.type === 'pk') badge = '<span class="tag pk">PK</span>';
                        else if (c.type === 'fk') badge = '<span class="tag fk">FK</span>';
                        
                        let displayType = c.type;
                        if (c.type === 'pk' || c.type === 'fk') displayType = 'bigint unsigned'; // standard assumption
                        
                        return `
                            <div class="column-card">
                                <div class="column-card-header">
                                    <span class="column-name">${c.name}</span>
                                    ${badge}
                                </div>
                                <div style="color: var(--text-muted); font-size: 0.8rem; font-family: monospace;">
                                    ${displayType}
                                </div>
                            </div>
                        `;
                    }).join('')}
                </div>
            `;
        }

        function selectTableCell(idx, element) {
            // Update active state
            document.querySelectorAll('.table-item').forEach(el => el.classList.remove('active'));
            element.classList.add('active');
            
            // Render content
            const table = activeNodeData.tables[idx];
            document.getElementById('splitMainContent').innerHTML = renderTableDetail(table);
            
            if (window.lucide) {
                lucide.createIcons();
            }
        }

        function filterTables() {
            const query = document.getElementById('tableSearch').value.toLowerCase();
            const items = document.querySelectorAll('.table-item');
            
            items.forEach(item => {
                const name = item.getAttribute('data-name').toLowerCase();
                if (name.includes(query)) {
                    item.style.display = 'flex';
                } else {
                    item.style.display = 'none';
                }
            });
        }

        function closeModal() {
            document.getElementById('modal').classList.remove('active');
        }

        // Close when clicking empty space
        document.getElementById('modal').addEventListener('click', (e) => {
            if (e.target.id === 'modal') {
                closeModal();
            }
        });

    </script>
</body>

</html>