<?php
require_once 'config.php';
$currentUser = isset($_SESSION['user_id']) ? [
    'role' => $_SESSION['role'],
    'location' => $_SESSION['location'] ?? null
] : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Tassne Alladaen – Production Dashboard</title>
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }
        body {
            background: #0b1120;
            color: #e2e8f0;
        }
        #app {
            max-width: 1600px;
            margin: 0 auto;
            padding: 1rem;
            position: relative;
        }
        :root {
            --glass: rgba(255, 255, 255, 0.1);
            --shadow-strong: 0 15px 35px rgba(0, 0, 0, 0.5);
            --green: #2ecc71;
        }

        /* LOGIN SECTION */
        #login-section {
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #0c1f33, #102a44);
        }
        .login-box {
            width: 360px;
            padding: 35px;
            background: var(--glass);
            backdrop-filter: blur(12px);
            border-radius: 18px;
            box-shadow: var(--shadow-strong);
            text-align: center;
        }
        .login-box h2 {
            color: white;
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        .login-box input, .login-box select {
            width: 100%;
            padding: 12px;
            margin-bottom: 18px;
            border: none;
            border-radius: 10px;
            background: rgba(255,255,255,0.9);
            font-size: 0.95rem;
        }
        .login-box input:focus, .login-box select:focus {
            outline: 2px solid #3b82f6;
        }
        .login-box button {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 10px;
            background: var(--green);
            color: #fff;
            font-size: 1.1em;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }
        .login-box button:hover {
            background: #27ae60;
            transform: translateY(-2px);
        }
        .login-error {
            color: #e74c3c;
            font-size: 0.9em;
            margin-top: 8px;
        }
        .btn {
            background: #2563eb;
            color: white;
            border: none;
            padding: 0.5rem 1.2rem;
            border-radius: 2rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.9rem;
        }
        .btn:hover {
            background: #1d4ed8;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(37,99,235,0.4);
        }
        .btn-logout {
            background: #475569;
        }
        .btn-logout:hover {
            background: #334155;
        }
        .hidden {
            display: none !important;
        }

        /* HEADER */
        .dashboard-header {
            background: #1e293b;
            padding: 1rem 1.5rem;
            border-radius: 3rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
            border: 1px solid #334155;
            box-shadow: 0 4px 6px rgba(0,0,0,0.3);
        }
        .header-Right {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .header-logo {
            height: 35px;
            width: auto;
            filter: brightness(0) invert(1);
        }
        .header-info h1 {
            font-size: 1.3rem;
            color: #f1f5f9;
        }
        .header-right {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            flex-wrap: wrap;
        }
        .badge-role {
            background: #2d3f55;
            padding: 0.4rem 1.2rem;
            border-radius: 2rem;
            font-weight: 600;
            color: #f1f5f9;
            border: 1px solid #4b5563;
            font-size: 0.9rem;
            white-space: nowrap;
        }
        .auto-badge {
            background: #8b5cf6;
            color: white;
            font-size: 0.6rem;
            padding: 0.2rem 0.5rem;
            border-radius: 30px;
            margin-left: 0.5rem;
            font-weight: 600;
            vertical-align: middle;
        }

        /* SUMMARY WIDGETS */
        .summary-widgets {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .widget {
            background: #1e293b;
            padding: 1rem;
            border-radius: 1rem;
            border: 1px solid #334155;
            text-align: center;
            transition: all 0.3s;
        }
        .widget:hover {
            border-color: #3b82f6;
            transform: translateY(-2px);
        }
        .widget-value {
            font-size: 1.5rem;
            font-weight: 700;
            color: #f1f5f9;
            margin: 0.3rem 0;
        }
        .widget-label {
            color: #94a3b8;
            font-size: 0.8rem;
        }

        /* FILTERS */
        .filters-container {
            background: #1e293b;
            padding: 1rem 1.5rem;
            border-radius: 3rem;
            margin-bottom: 1.5rem;
            display: flex;
            gap: 1.5rem;
            flex-wrap: wrap;
            border: 1px solid #334155;
        }
        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .filter-group span {
            font-weight: 600;
            color: #94a3b8;
            font-size: 0.9rem;
        }
        .filter-select, .filter-input {
            background: #0f172a;
            border: 1px solid #334155;
            border-radius: 2rem;
            padding: 0.4rem 1rem;
            color: white;
            min-width: 140px;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 0.85rem;
        }
        .filter-select:hover, .filter-input:hover {
            border-color: #3b82f6;
        }
        .filter-select:focus, .filter-input:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59,130,246,0.3);
        }

        /* LOCATIONS */
        .locations-section {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }
        .location-card {
            background: #1e293b;
            border-radius: 1.5rem;
            padding: 1.2rem;
            border: 1px solid #334155;
            transition: all 0.3s;
        }
        .location-card:hover {
            border-color: #3b82f6;
            box-shadow: 0 8px 20px rgba(0,0,0,0.4);
        }
        .location-title {
            font-size: 1.5rem;
            font-weight: 600;
            margin-bottom: 1rem;
            padding-left: 0.8rem;
            border-left: 5px solid #3b82f6;
            color: #f1f5f9;
        }

        /* MACHINES GRID */
        .machines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 1rem;
        }

        /* MACHINE CARD */
        .machine-card {
            background: #ffffff;
            color: #1e293b;
            border-radius: 20px;
            padding: 1.2rem;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            position: relative;
        }
        .machine-card:hover {
            transform: translateY(-2px);
            border-color: #3b82f6;
            box-shadow: 0 8px 15px rgba(0,0,0,0.1);
        }
        .machine-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
            flex-wrap: wrap;
        }
        .machine-name {
            font-weight: 700;
            font-size: 1rem;
            color: #0f172a;
        }
        .machine-id {
            font-size: 0.65rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.15rem 0.5rem;
            border-radius: 30px;
            font-weight: 600;
        }
        .status-badge {
            display: inline-block;
            padding: 0.2rem 1rem;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 700;
            text-transform: uppercase;
            margin: 0.2rem 0;
        }
        .status-running { background: #2ecc71; color: white; }
        .status-stopped { background: #e74c3c; color: white; }
        .status-completed { background: #3498db; color: white; }
        .status-idle { background: #94a3b8; color: white; }

        /* ORDER BADGE */
        .order-badge {
            display: inline-block;
            background: #8b5cf6;
            color: white;
            font-size: 0.65rem;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            margin: 0.2rem 0;
            font-weight: 600;
            max-width: 100%;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            border: 1px solid #7c3aed;
            box-shadow: 0 2px 4px rgba(139,92,246,0.3);
        }

        /* CUSTOMER BADGE - Only visible to managers */
        .customer-badge {
            display: inline-block;
            background: #3b82f6;
            color: white;
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            margin: 0.3rem 0;
            font-weight: 600;
            width: 100%;
            text-align: center;
            border: 1px solid #2563eb;
            box-shadow: 0 2px 4px rgba(59,130,246,0.3);
        }

        /* BRAND BADGE */
        .brand-badge {
            display: inline-block;
            background: #8b5cf6;
            color: white;
            font-size: 0.7rem;
            padding: 0.3rem 0.8rem;
            border-radius: 30px;
            margin: 0.3rem 0;
            font-weight: 700;
            width: 100%;
            text-align: center;
            border: 1px solid #7c3aed;
            box-shadow: 0 2px 8px rgba(139, 92, 246, 0.4);
            letter-spacing: 0.3px;
        }

        /* DESCRIPTION BOX */
        .description-box {
            font-size: 0.65rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.4rem 0.5rem;
            border-radius: 30px;
            margin: 0.3rem 0;
            text-align: center;
            border-left: 2px solid #3b82f6;
            line-height: 1.4;
            word-break: break-word;
        }

        /* ORDER PROGRESS */
        .order-progress {
            background: #f0f9ff;
            padding: 0.5rem;
            border-radius: 30px;
            margin: 0.5rem 0;
            border: 1px solid #bae6fd;
        }
        .order-progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.7rem;
            margin-bottom: 0.3rem;
        }
        .order-progress-label {
            color: #0369a1;
            font-weight: 600;
        }
        .order-progress-value {
            color: #0284c7;
            font-weight: 700;
        }

        /* PRODUCT INFO */
        .product-item-name {
            font-weight: 600;
            font-size: 0.85rem;
            color: #0f172a;
            margin: 0.4rem 0 0.3rem;
            text-align: center;
            background: #f8fafc;
            padding: 0.2rem;
            border-radius: 30px;
        }
        .product-details-row {
            display: flex;
            justify-content: center;
            gap: 0.8rem;
            margin-bottom: 0.5rem;
            font-size: 0.75rem;
            flex-wrap: wrap;
        }
        .product-size-badge {
            background: #e2e8f0;
            padding: 0.15rem 0.8rem;
            border-radius: 30px;
            color: #475569;
            font-weight: 600;
        }
        .product-color-display {
            display: flex;
            align-items: center;
            gap: 0.3rem;
            background: #f1f5f9;
            padding: 0.15rem 0.8rem;
            border-radius: 30px;
        }
        .color-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            display: inline-block;
        }
        .color-dot.orange { background: #f97316; }
        .color-dot.blue { background: #3b82f6; }
        .color-dot.grey { background: #6b7280; }
        .color-dot.green { background: #22c55e; }
        .color-dot.red { background: #ef4444; }
        .color-dot.white { background: #ffffff; border: 1px solid #e2e8f0; }
        .color-dot.yellow { background: #eab308; }
        .color-dot.brown { background: #92400e; }
        .color-dot.black { background: #000000; }

        /* SPEED INDICATOR */
        .speed-indicator {
            background: #eef2ff;
            padding: 0.3rem 1rem;
            border-radius: 30px;
            margin: 0.4rem 0;
            display: flex;
            flex-direction: column;
            gap: 5px;
        }
        .speed-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
        }
        .speed-value {
            background: #4f46e5;
            color: white;
            padding: 0.15rem 1rem;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.8rem;
        }
        .speed-edit-btn {
            background: transparent;
            border: none;
            color: #4f46e5;
            font-size: 1rem;
            cursor: pointer;
            padding: 0 5px;
        }
        .speed-edit-btn:hover {
            color: #6366f1;
            transform: scale(1.1);
        }
        .production-rate {
            background: #f1f5f9;
            padding: 5px 10px;
            border-radius: 30px;
            font-size: 0.75rem;
            display: flex;
            justify-content: space-between;
        }
        .rate-value {
            color: #2563eb;
            font-weight: 600;
        }
        .size-info-badge {
            font-size: 0.7rem;
            color: #64748b;
            background: #f1f5f9;
            padding: 0.2rem 0.5rem;
            border-radius: 30px;
            display: inline-block;
        }

        /* QUANTITY DISPLAY */
        .quantity-display {
            display: flex;
            justify-content: space-between;
            background: #f8fafc;
            padding: 0.4rem 1rem;
            border-radius: 30px;
            margin: 0.4rem 0;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .target-qty { color: #f59e0b; }
        .produced-qty { color: #10b981; }

        /* TIME PANEL */
        .time-panel {
            background: #f0f9ff;
            padding: 0.5rem;
            border-radius: 30px;
            margin: 0.5rem 0;
            display: flex;
            justify-content: space-around;
            font-size: 0.75rem;
            border: 1px solid #bae6fd;
            flex-wrap: wrap;
        }
        .time-item {
            text-align: center;
            min-width: 100px;
        }
        .time-label {
            color: #0369a1;
            font-size: 0.6rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .time-value {
            font-weight: 700;
            color: #0284c7;
            font-size: 0.8rem;
        }

        /* PROGRESS BAR */
        .progress-container {
            margin: 0.5rem 0;
        }
        .progress-header {
            display: flex;
            justify-content: space-between;
            font-size: 0.75rem;
            color: #64748b;
            margin-bottom: 0.2rem;
            font-weight: 600;
        }
        .progress-bar {
            width: 100%;
            height: 10px;
            background: #e2e8f0;
            border-radius: 30px;
            overflow: hidden;
            box-shadow: inset 0 1px 3px rgba(0,0,0,0.1);
        }
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #f59e0b, #fbbf24);
            border-radius: 30px;
            transition: width 0.3s ease;
        }
        .progress-fill.complete {
            background: linear-gradient(90deg, #10b981, #34d399);
        }

        /* UPDATE CONTROLS */
        .update-row {
            display: flex;
            align-items: center;
            gap: 5px;
            margin: 8px 0;
            flex-wrap: wrap;
        }
        .update-label {
            font-size: 0.75rem;
            width: 45px;
            color: #475569;
            font-weight: 600;
        }
        .qty-btn-small {
            width: 30px;
            height: 30px;
            border: none;
            border-radius: 50%;
            background: #f1f5f9;
            color: #1e293b;
            font-weight: 700;
            font-size: 1rem;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            border: 1px solid #e2e8f0;
        }
        .qty-btn-small:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }
        .qty-btn-small:active {
            transform: scale(0.9);
        }
        .qty-input-small {
            width: 70px;
            padding: 0.3rem;
            border: 2px solid #e2e8f0;
            border-radius: 30px;
            text-align: center;
            font-size: 0.8rem;
            font-weight: 600;
            background: white;
        }
        .small-btn {
            padding: 0.3rem 0.8rem;
            background: #8b5cf6;
            color: white;
            border: none;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .small-btn:hover {
            background: #7c3aed;
            transform: translateY(-2px);
        }

        /* BUTTONS */
        .start-btn {
            background: #3b82f6;
            color: white;
            border: none;
            padding: 0.5rem;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.85rem;
            cursor: pointer;
            width: 100%;
            margin-top: 0.5rem;
            transition: all 0.2s;
        }
        .start-btn:hover {
            background: #2563eb;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(37,99,235,0.3);
        }
        .reset-btn {
            width: 100%;
            padding: 0.5rem;
            background: #f59e0b;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            margin: 0.3rem 0;
            transition: all 0.2s;
        }
        .reset-btn:hover {
            background: #d97706;
            transform: translateY(-2px);
        }
        .waste-btn {
            width: 100%;
            padding: 0.5rem;
            background: #ef4444;
            color: white;
            border: none;
            border-radius: 30px;
            font-weight: 700;
            font-size: 0.8rem;
            cursor: pointer;
            margin: 0.3rem 0;
            transition: all 0.2s;
        }
        .waste-btn:hover {
            background: #dc2626;
            transform: translateY(-2px);
        }

        /* ACTION BUTTONS */
        .action-buttons {
            display: flex;
            gap: 5px;
            margin-top: 10px;
        }
        .action-buttons .card-btn {
            flex: 1;
            padding: 0.5rem;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.7rem;
        }
        .action-buttons .card-btn:hover {
            transform: translateY(-2px);
        }
        .edit-btn { background: #8b5cf6; color: white; }
        .stop-btn { background: #ef4444; color: white; }
        .resume-btn { background: #f59e0b; color: white; }
        .complete-btn { background: #10b981; color: white; }

        /* STOP REASON DISPLAY */
        .stop-reason-display {
            background: #fee2e2;
            color: #b91c1c;
            padding: 0.4rem;
            border-radius: 30px;
            text-align: center;
            margin: 0.4rem 0;
            font-size: 0.75rem;
            font-weight: 600;
        }
        .completed-message {
            background: #dbeafe;
            border-radius: 30px;
            padding: 0.4rem;
            margin: 0.4rem 0;
            color: #1e40af;
            font-size: 0.75rem;
            font-weight: 600;
            text-align: center;
        }

        /* MODAL STYLES */
        .modal {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
        }
        .modal-backdrop {
            position: absolute;
            width: 100%; height: 100%;
            background: rgba(0,0,0,0.8);
            backdrop-filter: blur(5px);
        }
        .modal-content {
            position: relative;
            background: #1e293b;
            border-radius: 24px;
            width: 90%;
            max-width: 500px;
            max-height: 90vh;
            overflow-y: auto;
            padding: 1.5rem;
            z-index: 1010;
            border: 1px solid #334155;
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5);
        }
        .modal-content h2, .modal-content h3 {
            color: #f1f5f9;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .modal-content label {
            color: #cbd5e1;
            display: block;
            margin-bottom: 0.2rem;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .modal-content select, .modal-content input, .modal-content textarea {
            width: 100%;
            padding: 0.6rem 1rem;
            border-radius: 30px;
            border: 1px solid #334155;
            background: #0f172a;
            color: white;
            margin-bottom: 0.8rem;
            font-size: 0.85rem;
        }
        .modal-content select:focus, .modal-content input:focus, .modal-content textarea:focus {
            outline: none;
            border-color: #3b82f6;
        }
        .modal-content textarea {
            border-radius: 20px;
            resize: vertical;
        }
        .checkbox-label {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: white;
            cursor: pointer;
            margin: 0.8rem 0;
        }
        .checkbox-label input {
            width: auto;
            margin: 0;
        }
        .modal-actions {
            display: flex;
            gap: 0.8rem;
            margin-top: 1rem;
        }
        .modal-actions button {
            flex: 1;
            padding: 0.6rem;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            font-size: 0.85rem;
            cursor: pointer;
            transition: all 0.2s;
        }
        .modal-actions button:hover {
            transform: translateY(-2px);
        }
        .modal-cancel {
            background: #475569;
            color: white;
        }
        .modal-confirm {
            background: #3b82f6;
            color: white;
        }
        .modal-confirm.warning {
            background: #ef4444;
        }
        .reason-list {
            max-height: 250px;
            overflow-y: auto;
            margin: 0.8rem 0;
            border: 1px solid #334155;
            border-radius: 12px;
        }
        .reason-item {
            padding: 0.6rem 1rem;
            border-bottom: 1px solid #334155;
            cursor: pointer;
            color: #e2e8f0;
            transition: all 0.2s;
            font-size: 0.85rem;
        }
        .reason-item:hover {
            background: #2d3f55;
        }
        .reason-item.selected {
            background: #3b82f6;
            color: white;
        }
        .reason-item:last-child {
            border-bottom: none;
        }

        /* REPORT MODALS */
        .report-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
            flex-wrap: wrap;
            gap: 0.8rem;
        }
        .report-header h3 {
            color: #f1f5f9;
            margin: 0;
            font-size: 1.1rem;
        }
        .report-header button {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            font-size: 0.8rem;
        }
        .report-header button:hover {
            transform: translateY(-2px);
        }
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            color: white;
            font-size: 0.75rem;
        }
        .metrics-table th {
            background: #2d3f55;
            padding: 0.5rem;
            position: sticky;
            top: 0;
        }
        .metrics-table td {
            padding: 0.5rem;
            border-bottom: 1px solid #334155;
        }
        .delete-btn {
            background: #ef4444;
            color: white;
            border: none;
            padding: 0.2rem 0.5rem;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
        }
        .delete-btn:hover {
            background: #dc2626;
            transform: scale(1.05);
        }
        .filter-btn {
            padding: 0.4rem 0.8rem;
            border: none;
            border-radius: 30px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s;
            background: #334155;
            color: #94a3b8;
            font-size: 0.75rem;
        }
        .filter-btn.active {
            background: #3b82f6;
            color: white;
        }
        .filter-btn:hover {
            transform: translateY(-2px);
        }
        .summary-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));
            gap: 0.8rem;
            margin-top: 1rem;
            padding: 0.8rem;
            background: #2d3f55;
            border-radius: 16px;
        }
        .stat-item {
            text-align: center;
        }
        .stat-label {
            font-size: 0.7rem;
            color: #94a3b8;
            margin-bottom: 0.2rem;
        }
        .stat-value {
            font-size: 1rem;
            font-weight: 700;
            color: #f1f5f9;
        }

        /* AUTO REFRESH INDICATOR */
        #refresh-indicator {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background: #1e293b;
            color: #94a3b8;
            padding: 12px 20px;
            border-radius: 40px;
            font-size: 0.95rem;
            z-index: 1000;
            border: 2px solid #3b82f6;
            transition: all 0.3s;
            font-weight: 600;
            box-shadow: 0 4px 15px rgba(0,0,0,0.5);
            letter-spacing: 0.5px;
        }
        #refresh-indicator.updating {
            background: #10b981;
            color: white;
            border-color: #059669;
            transform: scale(1.05);
        }
        footer {
            margin-top: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.8rem;
        }
        .loading {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s infinite;
        }
        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* PRODUCTION RATE DISPLAY */
        .rate-card {
            background: linear-gradient(135deg, #1e40af, #3b82f6);
            border-radius: 12px;
            padding: 12px;
            margin: 10px 0;
            color: white;
        }
        .rate-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            text-align: center;
        }
        .rate-item {
            background: rgba(255,255,255,0.1);
            padding: 8px;
            border-radius: 8px;
        }
        .rate-label {
            font-size: 0.65rem;
            opacity: 0.8;
        }
        .rate-number {
            font-size: 1.2rem;
            font-weight: 700;
        }

        /* MOBILE STYLING - FIXED for speed toggle */
        @media (max-width: 768px) {
            #app { padding: 0.5rem; }
            .dashboard-header { padding: 0.6rem; }
            .header-left { gap: 0.5rem; }
            .header-info h1 { font-size: 1rem; }
            .header-right { gap: 0.3rem; }
            .btn { padding: 0.3rem 0.6rem; font-size: 0.7rem; }
            .badge-role { font-size: 0.7rem; padding: 0.2rem 0.8rem; }
            .summary-widgets { grid-template-columns: repeat(2, 1fr); gap: 0.5rem; }
            .widget { padding: 0.6rem; }
            .widget-value { font-size: 1.2rem; }
            .filters-container { padding: 0.6rem; gap: 0.5rem; }
            .filter-group span { font-size: 0.7rem; }
            .filter-select, .filter-input { padding: 0.3rem 0.6rem; font-size: 0.7rem; min-width: 100px; }
            .machines-grid { grid-template-columns: 1fr; }
            .machine-card { padding: 0.8rem; }
            .order-badge { font-size: 0.55rem; }
            .customer-badge { font-size: 0.6rem; padding: 0.2rem 0.6rem; }
            .brand-badge { font-size: 0.6rem; padding: 0.2rem 0.6rem; }
            .description-box { font-size: 0.55rem; }
            .product-item-name { font-size: 0.75rem; }
            .speed-indicator { font-size: 0.7rem; }
            .speed-value { font-size: 0.7rem; }
            
            /* ✅ FIXED: Speed edit button on mobile */
            .speed-edit-btn {
                font-size: 1rem !important;
                padding: 0 5px !important;
                display: inline-block !important;
                min-width: 30px !important;
                min-height: 30px !important;
            }
            
            .production-rate { font-size: 0.65rem; }
            .quantity-display { font-size: 0.7rem; }
            .time-panel { font-size: 0.65rem; }
            .time-item { min-width: 70px; }
            .update-row { gap: 3px; }
            .update-label { font-size: 0.65rem; width: 35px; }
            .qty-btn-small { width: 25px; height: 25px; font-size: 0.8rem; }
            .qty-input-small { width: 50px; font-size: 0.65rem; }
            .small-btn { padding: 0.2rem 0.5rem; font-size: 0.6rem; }
            .action-buttons { gap: 0.2rem; }
            .card-btn { font-size: 0.65rem; padding: 0.3rem; }
            #refresh-indicator { bottom: 10px; right: 10px; font-size: 0.7rem; padding: 8px 12px; }
        }

        /* BACKGROUND ENGINE INDICATOR */
        .bg-engine-indicator {
            position: fixed;
            top: 10px;
            right: 10px;
            background: #1e293b;
            border: 2px solid #4ade80;
            color: #4ade80;
            padding: 6px 18px;
            border-radius: 40px;
            font-size: 0.8rem;
            font-weight: 700;
            z-index: 9999;
            box-shadow: 0 0 15px rgba(74, 222, 128, 0.3);
            animation: pulse 2s infinite;
            letter-spacing: 0.5px;
        }
        @keyframes pulse {
            0% { opacity: 0.8; box-shadow: 0 0 10px rgba(74, 222, 128, 0.3); }
            50% { opacity: 1; box-shadow: 0 0 20px rgba(74, 222, 128, 0.6); }
            100% { opacity: 0.8; box-shadow: 0 0 10px rgba(74, 222, 128, 0.3); }
        }

        /* ACCURACY BADGE */
        .accuracy-badge {
            display: inline-block;
            padding: 0.2rem 0.8rem;
            border-radius: 30px;
            font-size: 0.65rem;
            font-weight: 600;
            margin: 0.2rem 0;
        }
        .accuracy-exact {
            background: #10b981;
            color: white;
        }
        .accuracy-warning {
            background: #f59e0b;
            color: white;
        }
        .accuracy-error {
            background: #ef4444;
            color: white;
        }
        .last-update {
            font-size: 0.6rem;
            color: #64748b;
            text-align: right;
            margin-top: 5px;
            border-top: 1px dashed #e2e8f0;
            padding-top: 5px;
        }

        /* RENAME MODAL STYLES */
        #rename-modal .modal-content {
            max-width: 400px;
        }
        #rename-machine-name {
            width: 100%;
            padding: 12px;
            border-radius: 30px;
            border: 2px solid #3b82f6;
            background: #0f172a;
            color: white;
            font-size: 1rem;
            margin: 10px 0;
        }
        #rename-machine-name:focus {
            outline: none;
            border-color: #60a5fa;
        }
        
        /* ✅ FIXED: Order Total Badge */
        .order-total-badge {
            background: #10b981;
            color: white;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.8rem;
            display: inline-block;
            margin-top: 8px;
            font-weight: bold;
        }
        
    </style>
</head>
<body>
<div id="app">
    <!-- LOGIN SECTION -->
    <section id="login-section" class="<?php echo $currentUser ? 'hidden' : ''; ?>">
        <div class="login-box">
            <h2>Production Login</h2>
            <form id="login-form">
                <input type="text" id="username" placeholder="Username" required autocomplete="off">
                <input type="password" id="password" placeholder="Password" required autocomplete="new-password">
                <select id="role-select">
                    <option value="operator">👤 Operator</option>
                    <option value="manager">👑 Manager</option>
                </select>
                <button type="submit">Login</button>
                <div id="login-error" class="login-error"></div>
            </form>
        </div>
    </section>

    <!-- DASHBOARD SECTION -->
    <section id="dashboard-section" class="<?php echo !$currentUser ? 'hidden' : ''; ?>">
        <!-- BACKGROUND ENGINE INDICATOR -->
        <div id="bg-engine-indicator" class="bg-engine-indicator" style="display: none;">
            ⚙️ EXACT 1 MIN UPDATES
        </div>

        <header class="dashboard-header">
            <div class="header-left">
                <img src="https://tacogroup.net/wp-content/uploads/2024/05/cropped-taco-Logo-300x73.png" alt="TACO" class="header-logo">
                <div class="header-info">
                    <h1>TASSNE ALLADAEN <span class="auto-badge">PRODUCTION</span></h1>
                </div>
            </div>
            <div class="header-right">
                <span class="badge-role">
                    <?php echo $currentUser ? ucfirst($currentUser['role']) . ($currentUser['location'] ? ' (' . $currentUser['location'] . ')' : '') : ''; ?>
                </span>
                <button id="btn-view-downtime" class="btn btn-logout">🔧 Downtime</button>
                <button id="btn-view-waste" class="btn btn-logout">⚠️ Waste</button>
                <button id="btn-view-metrics" class="btn btn-logout">📊 Report</button>
                <button id="btn-material-mgmt" class="btn" style="background: linear-gradient(135deg, #8b5cf6, #ec489a); border: none;" onclick="openMaterialMgmt()">📦 Materials</button>
                <button id="btn-logout" class="btn btn-logout">🚪 Logout</button>
            </div>
        </header>

        <!-- SUMMARY WIDGETS -->
        <div class="summary-widgets" id="summary-widgets">
            <div class="widget"><div class="widget-label">Loading...</div><div class="widget-value">—</div></div>
            <div class="widget"><div class="widget-label">Loading...</div><div class="widget-value">—</div></div>
            <div class="widget"><div class="widget-label">Loading...</div><div class="widget-value">—</div></div>
            <div class="widget"><div class="widget-label">Loading...</div><div class="widget-value">—</div></div>
        </div>

        <!-- FILTERS -->
        <div class="filters-container">
            <div class="filter-group">
                <span>📍 Location:</span>
                <select id="filter-location" class="filter-select">
                    <option value="all">All Locations</option>
                    <option value="Modan">Modan</option>
                    <option value="Baldeya">Baldeya</option>
                    <option value="Al-Khraj">Al-Khraj</option>
                </select>
            </div>
            <div class="filter-group">
                <span>⚡ Status:</span>
                <select id="filter-status" class="filter-select">
                    <option value="all">All Status</option>
                    <option value="idle">⏳ Free</option>
                    <option value="running">▶ Running</option>
                    <option value="stopped">⛔ Stopped</option>
                    <option value="completed">✅ Completed</option>
                </select>
            </div>
            <div class="filter-group">
                <span>🔍 Search:</span>
                <input type="search" id="filter-search" class="filter-input" placeholder="Machine or product..." autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" readonly onfocus="this.removeAttribute('readonly')" value="">
            </div>
        </div>

        <!-- LOCATIONS CONTAINER -->
        <section id="locations" class="locations-section">
            <div style="text-align:center; padding:2rem;">
                <div class="loading"></div>
                <p>Loading machines...</p>
            </div>
        </section>

        <!-- PRODUCTION START MODAL -->
        <div id="production-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h2 id="modal-machine-name">Machine</h2>
                
                <label>📋 Work Order #:</label>
                <input type="text" id="modal-work-order" value="STOCK" readonly style="background:#2d3f55; color:#f1f5f9; font-weight:600;">
                
                <!-- Description Preview -->
                <div id="modal-description-preview" style="display: none;"></div>
                
                <label>📦 Item Type:</label>
                <select id="modal-product">
                    <option value="">Select Item</option>
                    <?php
                    $products = [
                         
                        'HDPE-CD 110 MM 5X33 MM',
                        'HDPE-CD 110 MM 4X38 MM',
                        'HDPE-CD 110 MM 7X29 MM',
                        'HDPE-CD 110 MM 3X42 MM',
                        'HDPE-CD EMPTY 110 MM',
                        'HDPE-CD EMPTY 90MM',
                        'HDPE-CD 90 MM 3X33 MM',
                        'HDPE-CD 77 MM 3X27.2 MM',
                        'HDPE-CD EMPTY 77MM',         
                        'HDPE PIPE 50MM X 4.6MM',
                        'HDPE 50 X 44 MINI DUCT',
                        'HDPE 40 X 2.4 MINI DUCT',
                        'HDPE 32 X 1.9 MINI DUCT',
                        'MICRO DUCT 2 WAY 12/8 MM',
                        'MICRO DUCT 12 WAY 12/8 MM',
                        'MICRO DUCT 16 WAY 20/16 MM',
                        'MICRO DUCT 4 WAY 25/20MM',
                        'MICRO DUCT 4 WAY 20/16MM',
                        'MICRO DUCT 7 WAY 25/20 MM',
                        'MICRO DUCT 7 WAY 20/16',
                        'HDPE 20 X 1.9 MINI DUCT',
                        'HDPE 32 X 2.9 MINI DUCT',
                        'HDPE 50 X 2.9 MINI DUCT',
                        'MICRO DUCT 4 WAY 20/16MM flat',
                        'HDPE PIPE',
                        'HDPE SUBDUCT PIPE',
                        'FLEXIBLE PIPE',
                        'Warning Tape',
                        'PVC PIPE',
                         'Rope'
                    ];
                    foreach ($products as $product) {
                        echo "<option value=\"$product\">$product</option>";
                    }
                    ?>
                </select>
                
                <label>🎨 Color:</label>
                <select id="modal-color">
                    <option value="">Select Color</option>
                    <?php
                    $colors = ['Orange', 'Blue', 'Grey', 'Green', 'Red', 'White', 'Yellow', 'Brown', 'Black'];
                    foreach ($colors as $color) {
                        echo "<option value=\"$color\">$color</option>";
                    }
                    ?>
                </select>
                
                <label>📏 Size:</label>
                <select id="modal-size">
                    <option value="">Select Size</option>
                    <option value="no_size">🚫 No Size (General Production)</option>
                    <?php
                    $sizes = [ '4mm','6mm','8mm','10mm','12mm', '16mm','18mm','20mm','25mm','27mm','27.2mm','28mm','29mm','32mm','33mm','38mm','39mm','40mm','42mm','50mm','63mm','75mm','90mm','110mm','125mm','140mm','160mm','180mm','190mm','200mm','225mm','250mm','280mm','290mm','300mm'];
                    foreach ($sizes as $size) {
                        echo "<option value=\"$size\">$size</option>";
                    }
                    ?>
                </select>
                
                <!-- Speed Control with Auto-Toggle -->
                <div style="background: #2d3f55; padding: 15px; border-radius: 16px; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px;">
                        <label style="color: white; font-size: 0.9rem; font-weight: 600;">
                            ⚡ Production Speed
                        </label>
                        <span style="background: #8b5cf6; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.7rem;">
                            Auto-Set by Size
                        </span>
                    </div>
                    
                    <!-- AUTO SPEED TOGGLE -->
                    <div style="display: flex; align-items: center; gap: 10px; margin: 15px 0; padding: 10px; background: #374151; border-radius: 30px;">
                        <label class="checkbox-label" style="display: flex; align-items: center; gap: 8px; margin: 0; cursor: pointer;">
                            <input type="checkbox" id="auto-speed-toggle" checked style="width: 18px; height: 18px;">
                            <span style="color: #f1f5f9; font-weight: 500;">🤖 Auto Speed (Recommended)</span>
                        </label>
                        <span style="color: #94a3b8; font-size: 0.75rem;">Disable to manually control speed</span>
                    </div>
                    
                    <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                        <input type="number" id="modal-speed" value="2.5" step="0.1" min="0.1" max="20"
                               style="flex: 1; padding: 8px; border-radius: 30px; border: none; text-align: center; font-size: 1rem; font-weight: 600; background: #0f172a; color: white;">
                        <span style="color: white;">m/min</span>
                    </div>
                    
                    <!-- PRODUCTION RATE DISPLAY -->
                    <div id="rate-display" style="background: #1e293b; border-radius: 12px; padding: 12px; margin: 10px 0;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <div style="text-align: center;">
                                <div style="font-size: 0.65rem; color: #94a3b8;">⏱️ 1 Meter =</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #4ade80;" id="seconds-per-meter">24.0 sec</div>
                            </div>
                            <div style="text-align: center;">
                                <div style="font-size: 0.65rem; color: #94a3b8;">📏 Per Minute =</div>
                                <div style="font-size: 1.1rem; font-weight: 700; color: #60a5fa;" id="meters-per-minute">2.5 m</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Speed Warning -->
                    <div id="speed-warning" style="font-size:0.75rem; margin-top:5px; text-align:center;"></div>
                    
                    <!-- Size Info Display -->
                    <div id="size-info" style="color: #4ade80; font-size: 0.8rem; margin: 10px 0; text-align: center;"></div>
                    
                    <!-- Time per meter display -->
                    <div id="time-per-meter" style="color: #94a3b8; text-align: center; margin-bottom: 10px; font-size: 0.8rem;"></div>
                    
                    <!-- Quick Presets -->
                    <div style="display: flex; gap: 8px; margin-top: 10px;">
                        <button type="button" onclick="setSpeedPreset('slow')" style="flex:1; padding:6px; border:none; border-radius:30px; background:#475569; color:white; font-size:0.75rem; cursor:pointer;">🐢 Slow</button>
                        <button type="button" onclick="setSpeedPreset('normal')" style="flex:1; padding:6px; border:none; border-radius:30px; background:#3b82f6; color:white; font-size:0.75rem; cursor:pointer;">⚡ Normal</button>
                        <button type="button" onclick="setSpeedPreset('fast')" style="flex:1; padding:6px; border:none; border-radius:30px; background:#f97316; color:white; font-size:0.75rem; cursor:pointer;">🚀 Fast</button>
                    </div>
                    
                    <!-- Manual Fine Adjustment -->
                    <div style="display: flex; gap: 10px; margin-top: 10px;">
                        <button type="button" onclick="adjustManualSpeed(-0.5)" style="flex:1; padding:5px; border:none; border-radius:30px; background:#334155; color:white; font-size:0.7rem;">⬇️ -0.5</button>
                        <button type="button" onclick="resetToDefault()" style="flex:1; padding:5px; border:none; border-radius:30px; background:#8b5cf6; color:white; font-size:0.7rem;">↺ Reset</button>
                        <button type="button" onclick="adjustManualSpeed(0.5)" style="flex:1; padding:5px; border:none; border-radius:30px; background:#334155; color:white; font-size:0.7rem;">⬆️ +0.5</button>
                    </div>
                </div>
                
                <label>🎯 Target Quantity (meters):</label>
                <input type="number" id="modal-qty" value="1000" min="1">
                
                <!-- Live Time Calculation -->
                <div style="background: #2d3f55; padding: 12px; border-radius: 12px; margin: 15px 0;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 5px;">
                        <span style="color: #94a3b8; font-size:0.8rem;">⏱️ Estimated time:</span>
                        <span style="color: #4ade80; font-weight: 600; font-size:0.8rem;" id="modal-estimated-time">6.7 hours</span>
                    </div>
                    <div style="display: flex; justify-content: space-between;">
                        <span style="color: #94a3b8; font-size:0.8rem;">⏰ Complete at:</span>
                        <span style="color: #60a5fa; font-weight: 600; font-size:0.8rem;" id="modal-complete-time">Today 8:45 PM</span>
                    </div>
                </div>
                
                <!-- Confirmation Checkbox -->
                <label class="checkbox-label" style="display: flex; align-items: center; gap: 10px; margin: 15px 0;">
                    <input type="checkbox" id="modal-confirm" style="width: 18px; height: 18px;">
                    <span style="color: #f1f5f9;">✅ I confirm this matches the Work Order</span>
                </label>
                
                <div class="modal-actions">
                    <button class="modal-cancel" id="modal-cancel">Cancel</button>
                    <button class="modal-confirm" id="modal-start">Start Production</button>
                </div>
            </div>
        </div>

        <!-- STOP REASON MODAL -->
        <div id="stop-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h3>⛔ Select Stop Reason</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;" id="stop-machine-name">Machine</p>
                <div class="reason-list" id="reason-list"></div>
                <div class="modal-actions">
                    <button class="modal-cancel" id="stop-cancel">Cancel</button>
                    <button class="modal-confirm warning" id="stop-confirm" disabled>Confirm Stop</button>
                </div>
            </div>
        </div>

        <!-- EDIT MACHINE MODAL -->
        <div id="edit-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h3>✏️ Edit Running Machine</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;" id="edit-machine-name">Machine</p>
                
                <label>📦 Product:</label>
                <select id="edit-product">
                    <option value="">Select Item</option>
                    <?php foreach ($products as $product) {
                        echo "<option value=\"$product\">$product</option>";
                    } ?>
                </select>
                
                <label>🎨 Color:</label>
                <select id="edit-color">
                    <option value="">Select Color</option>
                    <?php foreach ($colors as $color) {
                        echo "<option value=\"$color\">$color</option>";
                    } ?>
                </select>
                
                <label>📏 Size:</label>
                <select id="edit-size">
                    <option value="">Select Size</option>
                    <option value="no_size">🚫 No Size (General)</option>
                    <?php foreach ($sizes as $size) {
                        echo "<option value=\"$size\">$size</option>";
                    } ?>
                </select>
                
                <label>⚡ Speed (m/min):</label>
                <input type="number" id="edit-speed" step="0.1" min="0.1" value="2.5">
                <div id="edit-speed-warning" style="color: #f59e0b; font-size:0.8rem; margin:5px 0;"></div>
                
                <div class="modal-actions">
                    <button class="modal-cancel" id="edit-cancel">Cancel</button>
                    <button class="modal-confirm" id="edit-confirm">Update Machine</button>
                </div>
            </div>
        </div>

        <!-- SPEED CHANGE MODAL -->
        <div id="speed-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h3>⚡ Change Speed</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;" id="speed-machine-name">Machine</p>
                <label>Current Speed:</label>
                <input type="number" id="speed-value" step="0.1" min="0.1">
                
                <!-- Live Rate Display in Speed Modal -->
                <div id="speed-rate-display" style="background: #2d3f55; border-radius: 12px; padding: 12px; margin: 15px 0;">
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <div style="text-align: center;">
                            <div style="font-size: 0.65rem; color: #94a3b8;">1 Meter =</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #4ade80;" id="speed-sec-per-meter">24.0 sec</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 0.65rem; color: #94a3b8;">Per Minute =</div>
                            <div style="font-size: 1.2rem; font-weight: 700; color: #60a5fa;" id="speed-meters-per-min">2.5 m</div>
                        </div>
                    </div>
                </div>
                
                <div id="speed-info" style="color: #94a3b8; margin-top:10px;"></div>
                <div class="modal-actions">
                    <button class="modal-cancel" id="speed-cancel">Cancel</button>
                    <button class="modal-confirm" id="speed-confirm">Update Speed</button>
                </div>
            </div>
        </div>

        <!-- RENAME MACHINE MODAL -->
        <div id="rename-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h3>✏️ Rename Machine</h3>
                <p style="color: #94a3b8; margin-bottom: 1rem;" id="rename-machine-current">Machine</p>
                
                <label>New Machine Name:</label>
                <input type="text" id="rename-machine-name" placeholder="Enter new name" maxlength="100">
                
                <div class="modal-actions">
                    <button class="modal-cancel" id="rename-cancel">Cancel</button>
                    <button class="modal-confirm" id="rename-confirm">Update Name</button>
                </div>
            </div>
        </div>

        <!-- WASTE REPORT MODAL -->
        <div id="waste-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content">
                <h3>⚠️ Report Waste/Damaged Items</h3>
                <div style="background: #2d3f55; padding: 1rem; border-radius: 12px; margin-bottom: 1rem;">
                    <div style="display: flex; justify-content: space-between; margin-bottom: 0.5rem;">
                        <span style="color: #94a3b8;">Machine:</span>
                        <span style="color: white; font-weight: 600;" id="waste-machine-name">Machine</span>
                    </div>
                </div>
                <label>📦 Product:</label>
                <select id="waste-product">
                    <option value="">Select Product</option>
                    <?php foreach ($products as $product) {
                        echo "<option value=\"$product\">$product</option>";
                    } ?>
                </select>
                <label>🎨 Color:</label>
                <select id="waste-color">
                    <option value="">Select Color</option>
                    <?php foreach ($colors as $color) {
                        echo "<option value=\"$color\">$color</option>";
                    } ?>
                </select>
                <label>📏 Size:</label>
                <select id="waste-size">
                    <option value="">Select Size</option>
                    <option value="no_size">🚫 No Size</option>
                    <?php foreach ($sizes as $size) {
                        echo "<option value=\"$size\">$size</option>";
                    } ?>
                </select>
                <label>⚠️ Waste Quantity (meters):</label>
                <input type="number" id="waste-quantity" placeholder="Enter quantity" min="1" required>
                <label>⚡ Waste Reason:</label>
                <select id="waste-reason">
                    <option value="electricity" selected>⚡ Electricity Shutdown</option>
                    <option value="mechanical">🔧 Mechanical Failure</option>
                    <option value="material">📦 Material Issue</option>
                    <option value="operator">👤 Operator Error</option>
                    <option value="other">❓ Other</option>
                </select>
                <label>📝 Notes:</label>
                <textarea id="waste-notes" rows="2" placeholder="Add any comments..."></textarea>
                <div class="modal-actions">
                    <button class="modal-cancel" id="waste-cancel">Cancel</button>
                    <button class="modal-confirm warning" id="waste-submit">Report Waste</button>
                </div>
            </div>
        </div>

        <!-- METRICS MODAL -->
        <div id="metrics-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content" style="max-width: 1200px;">
                <div class="report-header">
                    <h3>📊 Production Report</h3>
                    <?php if ($currentUser && $currentUser['role'] === 'manager'): ?>
                    <button id="btn-clear-report" class="btn" style="background:#ef4444;">🗑️ Clear All</button>
                    <?php endif; ?>
                    <button id="btn-export-excel" class="btn" style="background:#2ecc71;">📥 Export Excel</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>Location</th>
                                <th>Machine</th>
                                <th>Work Order</th>
                                <th>Product</th>
                                <th>Brand</th>
                                <th>Customer</th>
                                <th>Description</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Target</th>
                                <th>Produced</th>
                                <th>Completed</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="metrics-table-body">
                            <tr><td colspan="13" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <button class="modal-cancel" id="btn-close-metrics" style="margin-top:1rem;">Close</button>
            </div>
        </div>

        <!-- DOWNTIME MODAL -->
        <div id="downtime-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content" style="max-width: 1200px;">
                <div class="report-header">
                    <h3>🔧 Downtime Report</h3>
                    <?php if ($currentUser && $currentUser['role'] === 'manager'): ?>
                    <button id="btn-clear-downtime" class="btn" style="background:#ef4444;">🗑️ Clear All</button>
                    <?php endif; ?>
                    <button id="btn-export-downtime" class="btn" style="background:#2ecc71;">📥 Export Excel</button>
                </div>
                <div style="display: flex; gap: 1rem; margin-bottom: 1rem; flex-wrap: wrap;">
                    <button class="filter-btn active" data-filter="today">Today</button>
                    <button class="filter-btn" data-filter="week">This Week</button>
                    <button class="filter-btn" data-filter="month">This Month</button>
                    <button class="filter-btn" data-filter="all">All Time</button>
                </div>
                <div style="overflow-x:auto;">
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>Machine</th><th>Location</th><th>Reason</th>
                                <th>Stopped</th><th>Resumed</th><th>Minutes</th><th>Status</th><th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="downtime-table-body">
                            <tr><td colspan="8" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="summary-stats">
                    <div class="stat-item"><div class="stat-label">Total</div><div class="stat-value" id="total-downtime">0</div></div>
                    <div class="stat-item"><div class="stat-label">Active</div><div class="stat-value" id="active-issues">0</div></div>
                    <div class="stat-item"><div class="stat-label">Average</div><div class="stat-value" id="avg-downtime">0</div></div>
                </div>
                <button class="modal-cancel" id="btn-close-downtime" style="margin-top:1rem;">Close</button>
            </div>
        </div>

        <!-- WASTE REPORT MODAL - View -->
        <div id="waste-report-modal" class="modal hidden">
            <div class="modal-backdrop"></div>
            <div class="modal-content" style="max-width: 1200px;">
                <div class="report-header">
                    <h3>⚠️ Waste/Damaged Items Report</h3>
                    <button id="btn-export-waste" class="btn" style="background:#2ecc71;">📥 Export Excel</button>
                </div>
                <div style="overflow-x:auto; margin-top:1rem;">
                    <table class="metrics-table">
                        <thead>
                            <tr>
                                <th>Date/Time</th>
                                <th>Machine</th>
                                <th>Product</th>
                                <th>Color</th>
                                <th>Size</th>
                                <th>Waste Qty</th>
                                <th>Reason</th>
                                <th>Notes</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody id="waste-table-body">
                            <tr><td colspan="9" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="summary-stats" style="grid-template-columns: repeat(4, 1fr);">
                    <div class="stat-item"><div class="stat-label">Total Waste</div><div class="stat-value" id="total-waste">0 m</div></div>
                    <div class="stat-item"><div class="stat-label">Electricity</div><div class="stat-value" id="electricity-waste">0 m</div></div>
                    <div class="stat-item"><div class="stat-label">Today</div><div class="stat-value" id="today-waste">0 m</div></div>
                    <div class="stat-item"><div class="stat-label">This Week</div><div class="stat-value" id="week-waste">0 m</div></div>
                </div>
                <button class="modal-cancel" id="waste-report-close" style="margin-top:1rem;">Close</button>
            </div>
        </div>

        <footer>© 2026 Tassne Alladaen – Production Dashboard</footer>
    </section>
</div>

<!-- Auto Refresh Indicator -->
<div id="refresh-indicator">⏱️ Next update in 60s</div>

<script>
// ========== CONFIGURATION ==========
const API_URL = '/production/api.php';

// Global variables
let selectedOrderItems = {};
let currentUser = <?php echo json_encode($currentUser); ?>;
let machineIntervals = {};

// Stop reasons array
const STOP_REASONS = [
    'Mechanical Breakdown', 'Material Shortage', 'Power Failure', 
    'Die Change', 'Operator Issue', 'Quality Check', 'Tool Change',
    'Cleaning', 'Maintenance', 'No Operator', 'Power Cut',
    'Raw Material Issue', 'Planning Hold', 'Break Time', 'Wait heater complete',
    'Setup Time', 'Waiting for Material','Laser printer issue',
];

// ========== PIPE SPEEDS (with NO SIZE option) ==========
const PIPE_SPEEDS = {
    'no_size': { normal: 2.5, min: 1.0, max: 5.0, time: 24.0 },
    '20mm': { normal: 4.8, min: 4.6, max: 5.0, time: 12.5 },
    '25mm': { normal: 4.2, min: 4.0, max: 4.4, time: 14.3 },
    '32mm': { normal: 3.8, min: 3.6, max: 4.0, time: 15.8 },
    '40mm': { normal: 3.2, min: 3.0, max: 3.4, time: 18.8 },
    '50mm': { normal: 2.8, min: 2.6, max: 3.0, time: 21.4 },
    '63mm': { normal: 2.2, min: 2.0, max: 2.4, time: 27.3 },
    '75mm': { normal: 1.8, min: 1.6, max: 2.0, time: 33.3 },
    '90mm': { normal: 1.2, min: 1.0, max: 1.4, time: 50.0 },
    '110mm': { normal: 0.73, min: 0.70, max: 0.76, time: 82.2 }
};

let machines = [];
let completedOrders = [];
let downtimeData = [];
let wasteData = [];
let selectedMachineId = null;
let selectedWasteMachineId = null;
let editMachineId = null;
let speedMachineId = null;
let selectedReason = '';
let currentSize = '';

// ✅ FIX: Tablet autofill bilkul shuruaat mein clear karo (DOMContentLoaded)
document.addEventListener('DOMContentLoaded', function() {
    const fs = document.getElementById('filter-search');
    if (fs) { fs.value = ''; fs.defaultValue = ''; }
    const fl = document.getElementById('filter-location');
    if (fl) fl.value = 'all';
    const fst = document.getElementById('filter-status');
    if (fst) fst.value = 'all';
});
// Extra: 100ms baad bhi clear karo (kuch browsers autofill delay se karte hain)
setTimeout(function() {
    const fs = document.getElementById('filter-search');
    if (fs && fs.value !== '') fs.value = '';
}, 100);
setTimeout(function() {
    const fs = document.getElementById('filter-search');
    if (fs && fs.value !== '') { fs.value = ''; renderDashboard && renderDashboard(); }
}, 500);
let refreshCountdown = 60;
let currentItemDescription = '';
let currentBrand = null;
let renameMachineId = null;

const colorMap = {
    'Orange': '#f97316', 'Blue': '#3b82f6', 'Grey': '#6b7280',
    'Green': '#22c55e', 'Red': '#ef4444', 'White': '#ffffff',
    'Yellow': '#eab308', 'Brown': '#92400e', 'Black': '#000000'
};

// ========== UI REFRESH ENGINE - CRON ONLY MODE ==========
let backgroundEngineStarted = false;
let backgroundInterval = null;
let backgroundRetryCount = 0;
let lastRefreshTime = null;

// System Monitor for UI refresh only
const SystemMonitor = {
    refreshes: [],
    
    logRefresh() {
        this.refreshes.push({
            time: new Date()
        });
        
        // Keep last 100 refreshes
        if (this.refreshes.length > 100) this.refreshes.shift();
    },
    
    getStatus() {
        return {
            totalRefreshes: this.refreshes.length,
            lastRefresh: this.refreshes[this.refreshes.length - 1]?.time || null
        };
    }
};

// Start UI refresh engine (NO PRODUCTION UPDATES)
function startUIEngine() {
    if (backgroundEngineStarted) {
        console.log('👁️ UI refresh engine already running');
        return;
    }
    
    console.log('%c👁️ UI REFRESH ENGINE STARTED', 'color: #60a5fa; font-size: 14px; font-weight: bold');
    console.log('   ✅ CRON handles ALL production updates');
    console.log('   ✅ Browser updates DISABLED');
    console.log('   ✅ UI refreshes every 30 seconds');
    
    backgroundEngineStarted = true;
    backgroundRetryCount = 0;
    
    // Show indicator
    const indicator = document.getElementById('bg-engine-indicator');
    if (indicator) {
        indicator.style.display = 'block';
        indicator.innerHTML = '⏳ CRON ONLY MODE';
        indicator.style.borderColor = '#60a5fa';
        indicator.style.color = '#60a5fa';
        indicator.style.animation = 'none';
    }
    
    // First run after 5 seconds
    setTimeout(refreshUI, 5000);
    
    // Then every 30 seconds (UI refresh only)
    backgroundInterval = setInterval(refreshUI, 30000);
}

// Main UI refresh function - NO UPDATES, just refresh display
async function refreshUI() {
    const refreshTime = new Date();
    console.log(`\n🔄 UI REFRESH at ${refreshTime.toLocaleTimeString()}`);
    
    try {
        if (!document.getElementById('dashboard-section').classList.contains('hidden')) {
            await loadMachines();
            console.log('   ✅ Display updated from database');
            SystemMonitor.logRefresh();
            lastRefreshTime = refreshTime;
        }
        
        const indicator = document.getElementById('bg-engine-indicator');
        if (indicator) {
            indicator.innerHTML = `👁️ ${refreshTime.toLocaleTimeString()}`;
            setTimeout(() => {
                indicator.innerHTML = '⏳ CRON ONLY';
            }, 2000);
        }
        
    } catch (error) {
        console.error('❌ UI refresh error:', error.message);
        handleRefreshError();
    }
}

function handleRefreshError() {
    backgroundRetryCount++;
    if (backgroundRetryCount > 3) {
        console.warn('⚠️ Multiple UI refresh failures, restarting...');
        restartUIEngine();
    }
}

function restartUIEngine() {
    if (backgroundInterval) {
        clearInterval(backgroundInterval);
        backgroundInterval = null;
    }
    backgroundEngineStarted = false;
    backgroundRetryCount = 0;
    
    setTimeout(() => {
        console.log('🔄 Restarting UI refresh engine...');
        startUIEngine();
    }, 30000);
}

// ========== BROWSER INTERVALS - COMPLETELY DISABLED ==========
function setMachineInterval(machineId, speed) {
    console.log(`⚠️ Browser production updates DISABLED for ${machineId} - using CRON only`);
    return;
}

// ========== UI REFRESH INDICATOR ==========
setInterval(() => {
    const indicator = document.getElementById('refresh-indicator');
    if (indicator) {
        const now = new Date();
        const seconds = 60 - now.getSeconds();
        indicator.innerHTML = `⏱️ CRON in ${seconds}s | UI 30s`;
        
        if (seconds <= 2) {
            indicator.classList.add('updating');
        } else {
            indicator.classList.remove('updating');
        }
    }
}, 1000);

// ========== MODIFIED LOGOUT FUNCTION ==========
document.getElementById('btn-logout')?.addEventListener('click', async () => {
    const result = await Swal.fire({
        title: '🚪 Logout?',
        html: `
            <div style="text-align: left;">
                <p style="color: #60a5fa; font-size: 1.1rem;">✅ Production continues with CRON!</p>
                <p style="font-size: 0.9rem; color: #94a3b8;">⏳ CRON updates every 60 seconds</p>
                <p style="font-size: 0.9rem; color: #94a3b8;">👁️ UI refreshes every 30 seconds</p>
                <hr style="border-color: #334155; margin: 10px 0;">
                <p style="font-size: 0.8rem; color: #4ade80;">📊 Browser updates DISABLED - CRON only</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#2563eb',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Yes, Logout',
        cancelButtonText: 'Cancel',
        background: '#1e293b',
        color: 'white'
    });
    
    if (result.isConfirmed) {
        Swal.fire({
            title: 'Logging out...',
            html: '<div class="loading" style="margin:20px auto;"></div><p style="color:#60a5fa;">CRON continues with 1-minute updates!</p>',
            allowOutsideClick: false,
            showConfirmButton: false,
            background: '#1e293b'
        });
        
        await fetch(`${API_URL}?action=logout`);
        currentUser = null;
        
        document.getElementById('login-section').classList.remove('hidden');
        document.getElementById('dashboard-section').classList.add('hidden');
        
        Swal.close();
        
        showNotification(
            '✅ Logged out - CRON continues with 1-minute updates',
            'success'
        );
    }
});

// ========== VISIBILITY HANDLER ==========
document.addEventListener('visibilitychange', function() {
    if (!document.hidden) {
        console.log('👋 Tab visible, refreshing UI...');
        if (!document.getElementById('dashboard-section').classList.contains('hidden')) {
            loadMachines();
        }
    } else {
        console.log('👻 Tab hidden, CRON running with 1-minute updates...');
    }
});

// ========== BEFORE UNLOAD HANDLER ==========
window.addEventListener('beforeunload', function() {
    console.log('🚪 Window closing, CRON continues with 1-minute updates...');
});

// ========== PAGE LOAD INITIALIZATION ==========
window.addEventListener('load', function() {
    console.log('%c📊 PRODUCTION DASHBOARD - CRON ONLY MODE', 'color: #60a5fa; font-size: 16px; font-weight: bold');
    console.log('═'.repeat(60));
    console.log('   ✅ CRON handles ALL production updates');
    console.log('   ✅ Browser updates DISABLED');
    console.log('   ✅ UI refreshes every 30 seconds');
    console.log('   ✅ No double updates possible');

    // ✅ FIX: Tablet browser autofill force clear - loadMachines se pehle
    const fs = document.getElementById('filter-search');
    if (fs) { fs.value = ''; fs.setAttribute('value', ''); }
    const fl = document.getElementById('filter-location');
    if (fl) fl.value = 'all';
    const fst = document.getElementById('filter-status');
    if (fst) fst.value = 'all';

    startUIEngine();
    loadMachines();
    loadWasteReport();
    
    console.log('✅ System ready - CRON ONLY MODE active');
});

// ========== ORIGINAL FUNCTIONS ==========
function trackOrderDistribution(orderId, items, totalQty) {
    if (!selectedOrderItems[orderId]) {
        selectedOrderItems[orderId] = {
            totalQty: totalQty || (items ? items.reduce((sum, item) => sum + (item.qty || 0), 0) : 0),
            allocatedQty: 0,
            machines: []
        };
    }
}

function updateOrderProgress(orderId) {
    if (!selectedOrderItems[orderId]) return;
    
    const order = selectedOrderItems[orderId];
    let totalAllocated = 0;
    
    machines.forEach(m => {
        if (m.work_order === orderId && m.status === 'running') {
            totalAllocated += m.target_qty || 0;
        }
    });
    
    order.allocatedQty = totalAllocated;
    const remaining = Math.max(0, order.totalQty - totalAllocated);
    const percent = order.totalQty > 0 ? (totalAllocated / order.totalQty) * 100 : 0;
    
    machines.forEach(m => {
        if (m.work_order === orderId) {
            const remainingEl = document.getElementById(`order-remaining-${orderId}`);
            const progressEl = document.getElementById(`order-progress-${orderId}`);
            
            if (remainingEl) {
                remainingEl.innerHTML = `${remaining.toLocaleString()}m remaining`;
            }
            if (progressEl) {
                progressEl.style.width = `${percent}%`;
            }
        }
    });
}

function calculateProductionRate(speed) {
    if (!speed || speed <= 0) return { secondsPerMeter: 0, metersPerMinute: 0 };
    
    const metersPerMinute = parseFloat(speed);
    const secondsPerMeter = 60 / metersPerMinute;
    
    return {
        metersPerMinute: metersPerMinute.toFixed(2),
        secondsPerMeter: secondsPerMeter.toFixed(1)
    };
}

function updateRateDisplay() {
    const speed = parseFloat(document.getElementById('modal-speed').value) || 2.5;
    const rate = calculateProductionRate(speed);
    
    document.getElementById('seconds-per-meter').textContent = rate.secondsPerMeter + ' sec';
    document.getElementById('meters-per-minute').textContent = rate.metersPerMinute + ' m';
}

function updateSpeedRateDisplay() {
    const speed = parseFloat(document.getElementById('speed-value').value) || 2.5;
    const rate = calculateProductionRate(speed);
    
    document.getElementById('speed-sec-per-meter').textContent = rate.secondsPerMeter + ' sec';
    document.getElementById('speed-meters-per-min').textContent = rate.metersPerMinute + ' m';
}

function formatDateTime(dateString) {
    if (!dateString) return '-';
    const date = new Date(dateString);
    const now = new Date();
    if (date.toDateString() === now.toDateString()) {
        return `Today ${date.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}`;
    }
    const yesterday = new Date(now);
    yesterday.setDate(yesterday.getDate() - 1);
    if (date.toDateString() === yesterday.toDateString()) {
        return `Yesterday ${date.toLocaleTimeString([], {hour:'2-digit', minute:'2-digit'})}`;
    }
    return date.toLocaleString();
}

function validateSpeed(size, speed) {
    if (!size || !PIPE_SPEEDS[size]) return true;
    const speedData = PIPE_SPEEDS[size];
    return speed >= speedData.min && speed <= speedData.max;
}

function showNotification(msg, type = 'success') {
    Swal.fire({
        text: msg,
        icon: type,
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true
    });
}

// ========== LOGIN ==========
document.getElementById('login-form')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    const loginBtn = document.querySelector('.login-box button');
    loginBtn.innerHTML = 'Logging in...';
    loginBtn.disabled = true;
    
    try {
        const res = await fetch(`${API_URL}?action=login`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                username: document.getElementById('username').value,
                password: document.getElementById('password').value,
                role: document.getElementById('role-select').value
            })
        });
        const data = await res.json();
        if (data.success) {
            location.reload();
        } else {
            document.getElementById('login-error').textContent = 'Invalid credentials';
            loginBtn.innerHTML = 'Login';
            loginBtn.disabled = false;
        }
    } catch (error) {
        document.getElementById('login-error').textContent = 'Connection error';
        loginBtn.innerHTML = 'Login';
        loginBtn.disabled = false;
    }
});

// ========== LOAD MACHINES ==========
async function loadMachines() {
    try {
        const res = await fetch(`${API_URL}?action=get_machines`);
        const data = await res.json();
        if (data.success) {
            machines = data.data;
            renderDashboard();
            updateSummaryWidgets();
            
            Object.keys(selectedOrderItems).forEach(orderId => {
                updateOrderProgress(orderId);
            });
        }
    } catch (error) {
        console.error('Error loading machines:', error);
        showNotification('Error loading machines', 'error');
    }
}

// ========== UPDATE SUMMARY WIDGETS ==========
function updateSummaryWidgets() {
    let totalProduction = 0;
    let activeMachines = 0;
    let totalWasteToday = 0;
    
    machines.forEach(m => {
        totalProduction += m.produced_qty || 0;
        if (m.status === 'running') activeMachines++;
    });
    
    wasteData.forEach(w => {
        const today = new Date().toDateString();
        if (w.reported_at && new Date(w.reported_at).toDateString() === today) {
            totalWasteToday += parseInt(w.waste_quantity) || 0;
        }
    });
    
    document.getElementById('summary-widgets').innerHTML = `
        <div class="widget">
            <div class="widget-label">Today's Production</div>
            <div class="widget-value">${totalProduction}m</div>
        </div>
        <div class="widget">
            <div class="widget-label">Active Machines</div>
            <div class="widget-value">${activeMachines}/${machines.length}</div>
        </div>
        <div class="widget">
            <div class="widget-label">Waste Today</div>
            <div class="widget-value">${totalWasteToday}m</div>
        </div>
        <div class="widget">
            <div class="widget-label">Efficiency</div>
            <div class="widget-value">${totalProduction > 0 ? ((totalProduction / (totalProduction + totalWasteToday)) * 100).toFixed(1) : '0'}%</div>
        </div>
    `;
}

// ========== API CALLS WITH VERSION CONTROL ==========
async function startMachine(id, data) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex !== -1) {
        machines[machineIndex] = {
            ...machines[machineIndex],
            status: 'running',
            current_product: data.product,
            current_description: data.description || null,
            current_brand: data.brand || null,
            current_customer: data.customer || null,
            current_color: data.color,
            current_size: data.size,
            target_qty: data.qty,
            produced_qty: 0,
            current_speed: data.speed,
            work_order: data.work_order,
            version: 1
        };
        renderDashboard();
        
        if (data.work_order && data.work_order !== 'STOCK') {
            trackOrderDistribution(data.work_order, null, data.order_total || data.qty);
            updateOrderProgress(data.work_order);
        }
    }
    
    hideProductionModal();
    showNotification(`✅ Machine started at ${data.speed} m/min`);
    
    try {
        await fetch(`${API_URL}?action=start_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id, 
                ...data,
                description: data.description || null,
                brand: data.brand || null,
                customer: data.customer || null
            })
        });
    } catch (error) {
        console.error('Sync error:', error);
    }
}

async function stopMachine(id, reason) {
    if (machineIntervals[id]) {
        clearInterval(machineIntervals[id]);
        delete machineIntervals[id];
    }
    
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex !== -1) {
        machines[machineIndex].status = 'stopped';
        machines[machineIndex].stop_reason = reason;
        renderDashboard();
    }
    
    hideStopModal();
    showNotification(`⛔ Stopped: ${reason}`);
    
    try {
        await fetch(`${API_URL}?action=stop_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({machine_id: id, reason})
        });
    } catch (error) {
        console.error('Sync error:', error);
    }
}

async function resumeMachine(id) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex !== -1) {
        machines[machineIndex].status = 'running';
        machines[machineIndex].stop_reason = null;
        renderDashboard();
    }
    
    showNotification('▶️ Machine resumed');
    
    try {
        await fetch(`${API_URL}?action=resume_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({machine_id: id})
        });
    } catch (error) {
        console.error('Sync error:', error);
    }
}

async function updateProducedQuantity(id, newQty) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex === -1) return;
    
    const machine = machines[machineIndex];
    const currentVersion = machine.version || 1;
    
    try {
        const response = await fetch(`${API_URL}?action=update_quantity`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id, 
                produced_qty: newQty,
                version: currentVersion
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            machines[machineIndex].produced_qty = newQty;
            machines[machineIndex].version = (currentVersion + 1);
            renderDashboard();
            showNotification(`✅ Quantity updated to ${newQty}m`, 'success');
        } else {
            if (result.message && result.message.includes('Version mismatch')) {
                showNotification('🔄 Data updated by CRON, reloading...', 'info');
                await loadMachines();
            } else {
                showNotification('❌ ' + (result.message || 'Update failed'), 'error');
            }
        }
        
    } catch (error) {
        console.error('API error:', error);
        showNotification('❌ Network error', 'error');
        await loadMachines();
    }
}

async function updateTargetQuantity(id, newTarget) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex === -1) return;
    
    const machine = machines[machineIndex];
    const currentVersion = machine.version || 1;
    
    try {
        const response = await fetch(`${API_URL}?action=update_target`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id, 
                target_qty: newTarget,
                version: currentVersion
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            machines[machineIndex].target_qty = newTarget;
            machines[machineIndex].version = (currentVersion + 1);
            renderDashboard();
            showNotification(`✅ Target updated to ${newTarget}m`, 'success');
        } else {
            if (result.message && result.message.includes('Version mismatch')) {
                showNotification('🔄 Data updated by CRON, reloading...', 'info');
                await loadMachines();
            } else {
                showNotification('❌ ' + (result.message || 'Update failed'), 'error');
            }
        }
        
    } catch (error) {
        console.error('API error:', error);
        showNotification('❌ Network error', 'error');
        await loadMachines();
    }
}

async function completeMachine(id) {
    if (machineIntervals[id]) {
        clearInterval(machineIntervals[id]);
        delete machineIntervals[id];
    }
    
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex !== -1) {
        const workOrder = machines[machineIndex].work_order;
        machines[machineIndex].status = 'completed';
        machines[machineIndex].completed_at = new Date().toISOString();
        renderDashboard();
        
        if (workOrder && workOrder !== 'STOCK') {
            updateOrderProgress(workOrder);
        }
    }
    
    Swal.fire({
        icon: 'success',
        title: 'Order Completed!',
        text: 'The order has been marked as completed.',
        timer: 2000,
        showConfirmButton: false
    });
    
    try {
        await fetch(`${API_URL}?action=complete_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id,
                work_order: machines[machineIndex]?.work_order || ''
            })
        });
    } catch (error) {
        console.error('Sync error:', error);
    }
}

async function resetMachine(id) {
    if (machineIntervals[id]) {
        clearInterval(machineIntervals[id]);
        delete machineIntervals[id];
    }
    
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex !== -1) {
        const workOrder = machines[machineIndex].work_order;
        
        machines[machineIndex].status = 'idle';
        machines[machineIndex].current_product = null;
        machines[machineIndex].current_description = null;
        machines[machineIndex].current_brand = null;
        machines[machineIndex].current_customer = null;
        machines[machineIndex].current_color = null;
        machines[machineIndex].current_size = null;
        machines[machineIndex].target_qty = 0;
        machines[machineIndex].produced_qty = 0;
        machines[machineIndex].stop_reason = null;
        machines[machineIndex].completed_at = null;
        machines[machineIndex].work_order = null;
        machines[machineIndex].version = 1;
        renderDashboard();
        
        if (workOrder && workOrder !== 'STOCK') {
            updateOrderProgress(workOrder);
        }
    }
    
    showNotification('Machine reset');
    
    try {
        await fetch(`${API_URL}?action=reset_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({machine_id: id})
        });
    } catch (error) {
        console.error('Sync error:', error);
    }
}

async function updateMachine(id, data) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex === -1) return false;
    
    const machine = machines[machineIndex];
    const currentVersion = machine.version || 1;
    
    try {
        const response = await fetch(`${API_URL}?action=update_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id,
                ...data,
                version: currentVersion
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            machines[machineIndex].current_product = data.product;
            machines[machineIndex].current_color = data.color;
            machines[machineIndex].current_size = data.size;
            machines[machineIndex].current_speed = data.speed;
            machines[machineIndex].version = (currentVersion + 1);
            renderDashboard();
            showNotification('✅ Machine updated', 'success');
            return true;
        } else {
            if (result.message && result.message.includes('Version mismatch')) {
                showNotification('🔄 Data updated by CRON, reloading...', 'info');
                await loadMachines();
            } else {
                showNotification('❌ ' + (result.message || 'Update failed'), 'error');
            }
            return false;
        }
    } catch (error) {
        console.error('Update error:', error);
        showNotification('❌ Network error', 'error');
        await loadMachines();
        return false;
    }
}

async function updateSpeed(id, speed) {
    const machineIndex = machines.findIndex(m => m.machine_id === id);
    if (machineIndex === -1) return false;
    
    const machine = machines[machineIndex];
    const currentVersion = machine.version || 1;
    
    try {
        const response = await fetch(`${API_URL}?action=update_speed`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: id,
                speed: speed,
                version: currentVersion
            })
        });
        
        const result = await response.json();
        
        if (result.success) {
            machines[machineIndex].current_speed = speed;
            machines[machineIndex].version = (currentVersion + 1);
            renderDashboard();
            showNotification('✅ Speed updated', 'success');
            return true;
        } else {
            if (result.message && result.message.includes('Version mismatch')) {
                showNotification('🔄 Data updated by CRON, reloading...', 'info');
                await loadMachines();
            } else {
                showNotification('❌ ' + (result.message || 'Update failed'), 'error');
            }
            return false;
        }
    } catch (error) {
        console.error('Speed update error:', error);
        showNotification('❌ Network error', 'error');
        await loadMachines();
        return false;
    }
}

// ========== RENAME MACHINE FUNCTIONS ==========
function showRenameModal(machineId, currentName) {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can rename machines', 'error');
        return;
    }
    
    renameMachineId = machineId;
    document.getElementById('rename-machine-current').innerHTML = `Current Name: <strong>${currentName}</strong>`;
    document.getElementById('rename-machine-name').value = currentName;
    document.getElementById('rename-modal').classList.remove('hidden');
}

function hideRenameModal() {
    document.getElementById('rename-modal').classList.add('hidden');
    renameMachineId = null;
}

// Rename confirm
document.getElementById('rename-confirm')?.addEventListener('click', async () => {
    if (!renameMachineId) return;
    
    const newName = document.getElementById('rename-machine-name').value.trim();
    
    if (!newName) {
        Swal.fire('Error', 'Please enter a machine name', 'warning');
        return;
    }
    
    if (newName.length > 100) {
        Swal.fire('Error', 'Name too long (max 100 characters)', 'warning');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Confirm Rename',
        text: `Change machine name to "${newName}"?`,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Yes, Rename',
        cancelButtonText: 'Cancel'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const response = await fetch(`${API_URL}?action=rename_machine`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({
                machine_id: renameMachineId,
                new_name: newName
            })
        });
        
        const data = await response.json();
        
        if (data.success) {
            showNotification('✅ Machine renamed successfully');
            hideRenameModal();
            await loadMachines();
        } else {
            Swal.fire('Error', data.message || 'Failed to rename machine', 'error');
        }
    } catch (error) {
        console.error('Rename error:', error);
        Swal.fire('Error', 'Network error', 'error');
    }
});

document.getElementById('rename-cancel')?.addEventListener('click', hideRenameModal);

async function reportWaste(data) {
    try {
        const res = await fetch(`${API_URL}?action=report_waste`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(data)
        });
        
        if (!res.ok) {
            return { success: false, message: `HTTP error ${res.status}` };
        }
        
        const text = await res.text();
        
        if (!text) {
            return { success: false, message: 'Empty response from server' };
        }
        
        try {
            return JSON.parse(text);
        } catch (e) {
            console.error('JSON parse error:', e);
            console.error('Response text:', text);
            return { success: false, message: 'Invalid server response' };
        }
    } catch (error) {
        return { success: false, message: error.message };
    }
}

async function deleteOrder(orderId) {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can delete', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Delete Order?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    await fetch(`${API_URL}?action=delete_order`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({order_id: orderId})
    });
    showNotification('Order deleted');
    loadMetricsReport();
}

async function deleteDowntimeRecord(id) {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can delete', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Delete Record?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    await fetch(`${API_URL}?action=delete_downtime`, {
        method: 'POST',
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({id})
    });
    showNotification('Record deleted');
    loadDowntimeReport(document.querySelector('.filter-btn.active')?.dataset.filter || 'today');
}

async function deleteWasteRecord(id) {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can delete', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Delete Waste Record?',
        text: 'This action cannot be undone!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    try {
        const res = await fetch(`${API_URL}?action=delete_waste`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({id})
        });
        const data = await res.json();
        if (data.success) {
            showNotification('Waste record deleted');
            await loadWasteReport();
        }
    } catch (error) {
        showNotification('Error: ' + error.message, 'error');
    }
}

async function clearReport() {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can clear all records', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Clear All Orders?',
        text: 'This will delete ALL completed orders!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    await fetch(`${API_URL}?action=clear_completed_orders`, {method: 'POST'});
    showNotification('✅ All cleared');
    loadMetricsReport();
}

async function clearDowntimeReport() {
    if (currentUser?.role !== 'manager') {
        Swal.fire('Error', 'Only managers can clear all records', 'error');
        return;
    }
    
    const result = await Swal.fire({
        title: 'Clear All Downtime?',
        text: 'This will delete ALL downtime records!',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#ef4444',
        cancelButtonColor: '#6b7280'
    });
    
    if (!result.isConfirmed) return;
    
    await fetch(`${API_URL}?action=clear_downtime`, {method: 'POST'});
    showNotification('✅ All cleared');
    loadDowntimeReport(document.querySelector('.filter-btn.active')?.dataset.filter || 'today');
}

// ========== PRODUCTION MODAL FUNCTIONS ==========
function showProductionModal(machineId, machineName) {
    selectedMachineId = machineId;
    document.getElementById('modal-machine-name').textContent = machineName;
    document.getElementById('modal-work-order').value = 'STOCK';
    document.getElementById('modal-product').value = '';
    document.getElementById('modal-color').value = '';
    document.getElementById('modal-size').value = '';
    document.getElementById('modal-speed').value = '2.5';
    document.getElementById('modal-qty').value = 1000;
    document.getElementById('modal-confirm').checked = false;
    document.getElementById('auto-speed-toggle').checked = true;
    document.getElementById('size-info').innerHTML = '👆 Select size OR choose "No Size" for general production';
    document.getElementById('time-per-meter').innerHTML = '';
    document.getElementById('speed-warning').innerHTML = '';
    
    updateRateDisplay();
    
    const descDiv = document.getElementById('modal-description-preview');
    if (descDiv) {
        descDiv.style.display = 'none';
        descDiv.innerHTML = '';
    }
    
    currentItemDescription = '';
    currentBrand = null;
    
    calculateProductionTime();
    document.getElementById('production-modal').classList.remove('hidden');
}

function hideProductionModal() {
    document.getElementById('production-modal').classList.add('hidden');
    selectedMachineId = null;
}

// ========== EDIT MODAL FUNCTIONS ==========
function showEditModal(machineId) {
    const machine = machines.find(m => m.machine_id === machineId);
    if (!machine) return;
    
    editMachineId = machineId;
    document.getElementById('edit-machine-name').textContent = machine.name;
    document.getElementById('edit-product').value = machine.current_product || '';
    document.getElementById('edit-color').value = machine.current_color || '';
    document.getElementById('edit-size').value = machine.current_size || '';
    document.getElementById('edit-speed').value = machine.current_speed || 2.5;
    
    document.getElementById('edit-modal').classList.remove('hidden');
}

function hideEditModal() {
    document.getElementById('edit-modal').classList.add('hidden');
    editMachineId = null;
}

// ========== SPEED MODAL FUNCTIONS ==========
function showSpeedModal(machineId, currentSpeed) {
    const machine = machines.find(m => m.machine_id === machineId);
    if (!machine) return;
    
    speedMachineId = machineId;
    document.getElementById('speed-machine-name').textContent = machine.name;
    document.getElementById('speed-value').value = currentSpeed;
    
    updateSpeedRateDisplay();
    
    const size = machine.current_size;
    const infoDiv = document.getElementById('speed-info');
    
    if (size && PIPE_SPEEDS[size]) {
        const speedData = PIPE_SPEEDS[size];
        infoDiv.innerHTML = `Recommended range: ${speedData.min} - ${speedData.max} m/min`;
    } else {
        infoDiv.innerHTML = '';
    }
    
    document.getElementById('speed-modal').classList.remove('hidden');
}

function hideSpeedModal() {
    document.getElementById('speed-modal').classList.add('hidden');
    speedMachineId = null;
}

// ========== SIZE SELECT - AUTO SPEED SET ==========
document.getElementById('modal-size').addEventListener('change', function() {
    const size = this.value;
    currentSize = size;
    
    if (size === 'no_size') {
        document.getElementById('modal-speed').value = 2.5;
        document.getElementById('size-info').innerHTML = '📦 General production - standard speed 2.5 m/min';
        document.getElementById('time-per-meter').innerHTML = '⏱️ = 24.0 seconds/meter (standard)';
        updateRateDisplay();
        calculateProductionTime();
        return;
    }
    
    if (PIPE_SPEEDS[size] && document.getElementById('auto-speed-toggle').checked) {
        const speedData = PIPE_SPEEDS[size];
        document.getElementById('modal-speed').value = speedData.normal;
        document.getElementById('size-info').innerHTML = 
            `✅ ${size} pipe: ${speedData.time.toFixed(1)} sec/meter (Range: ${speedData.min}-${speedData.max})`;
        
        const secPerMeter = (60 / speedData.normal).toFixed(1);
        document.getElementById('time-per-meter').innerHTML = 
            `⏱️ = ${secPerMeter} seconds/meter`;
        
        updateRateDisplay();
        calculateProductionTime();
    }
});

document.getElementById('edit-size').addEventListener('change', function() {
    const size = this.value;
    if (size && size !== 'no_size' && PIPE_SPEEDS[size]) {
        document.getElementById('edit-speed').value = PIPE_SPEEDS[size].normal;
        const speedData = PIPE_SPEEDS[size];
        document.getElementById('edit-speed-warning').innerHTML = 
            `Range: ${speedData.min} - ${speedData.max} m/min`;
    } else if (size === 'no_size') {
        document.getElementById('edit-speed').value = 2.5;
        document.getElementById('edit-speed-warning').innerHTML = 'Standard speed for general production';
    }
});

document.getElementById('edit-speed').addEventListener('input', function() {
    const size = document.getElementById('edit-size').value;
    const speed = parseFloat(this.value);
    
    if (size && size !== 'no_size' && PIPE_SPEEDS[size]) {
        const speedData = PIPE_SPEEDS[size];
        const warningDiv = document.getElementById('edit-speed-warning');
        
        if (speed < speedData.min) {
            warningDiv.innerHTML = `⚠️ Too slow! Minimum: ${speedData.min} m/min`;
            warningDiv.style.color = '#f97316';
        } else if (speed > speedData.max) {
            warningDiv.innerHTML = `⚠️ Too fast! Maximum: ${speedData.max} m/min`;
            warningDiv.style.color = '#ef4444';
        } else {
            warningDiv.innerHTML = `✅ Good speed (${speedData.min} - ${speedData.max} m/min)`;
            warningDiv.style.color = '#4ade80';
        }
    }
});

document.getElementById('modal-speed')?.addEventListener('input', function() {
    const size = document.getElementById('modal-size').value;
    const speed = parseFloat(this.value);
    
    updateRateDisplay();
    
    if (size && size !== 'no_size' && PIPE_SPEEDS[size] && document.getElementById('auto-speed-toggle').checked) {
        const speedData = PIPE_SPEEDS[size];
        const warningDiv = document.getElementById('speed-warning');
        
        if (speed < speedData.min) {
            warningDiv.innerHTML = `⚠️ Too slow! Minimum: ${speedData.min} m/min`;
            warningDiv.style.color = '#f97316';
        } else if (speed > speedData.max) {
            warningDiv.innerHTML = `⚠️ Too fast! Maximum: ${speedData.max} m/min`;
            warningDiv.style.color = '#ef4444';
        } else {
            warningDiv.innerHTML = `✅ Good speed (${speedData.min} - ${speedData.max} m/min)`;
            warningDiv.style.color = '#4ade80';
        }
    }
    
    const secPerMeter = (60 / speed).toFixed(1);
    document.getElementById('time-per-meter').innerHTML = `⏱️ = ${secPerMeter} seconds/meter`;
    
    calculateProductionTime();
});

// Auto-speed toggle
document.getElementById('auto-speed-toggle')?.addEventListener('change', function() {
    const size = document.getElementById('modal-size').value;
    
    if (this.checked && size && size !== 'no_size' && PIPE_SPEEDS[size]) {
        document.getElementById('modal-speed').value = PIPE_SPEEDS[size].normal;
        document.getElementById('modal-speed').dispatchEvent(new Event('input'));
        showNotification('✅ Auto-speed enabled', 'info');
    } else if (!this.checked) {
        showNotification('⚙️ Manual speed mode enabled', 'info');
    }
});

document.getElementById('speed-value').addEventListener('input', function() {
    const machine = machines.find(m => m.machine_id === speedMachineId);
    if (!machine) return;
    
    updateSpeedRateDisplay();
    
    const size = machine.current_size;
    const speed = parseFloat(this.value);
    const infoDiv = document.getElementById('speed-info');
    
    if (size && size !== 'no_size' && PIPE_SPEEDS[size]) {
        const speedData = PIPE_SPEEDS[size];
        
        if (speed < speedData.min) {
            infoDiv.innerHTML = `⚠️ Below minimum! Recommended: ${speedData.min} - ${speedData.max}`;
            infoDiv.style.color = '#f97316';
        } else if (speed > speedData.max) {
            infoDiv.innerHTML = `⚠️ Above maximum! Recommended: ${speedData.min} - ${speedData.max}`;
            infoDiv.style.color = '#ef4444';
        } else {
            infoDiv.innerHTML = `✅ Good speed (${speedData.min} - ${speedData.max} m/min)`;
            infoDiv.style.color = '#4ade80';
        }
    }
});

function setSpeedPreset(mode) {
    if (!currentSize || !PIPE_SPEEDS[currentSize]) {
        Swal.fire('Error', 'Please select size first', 'warning');
        return;
    }
    
    const speedData = PIPE_SPEEDS[currentSize];
    let newSpeed;
    
    if (mode === 'slow') newSpeed = speedData.min;
    else if (mode === 'fast') newSpeed = speedData.max;
    else newSpeed = speedData.normal;
    
    document.getElementById('modal-speed').value = newSpeed.toFixed(2);
    document.getElementById('modal-speed').dispatchEvent(new Event('input'));
}

function adjustManualSpeed(change) {
    const currentSpeed = parseFloat(document.getElementById('modal-speed').value);
    const newSpeed = currentSpeed + change;
    
    if (newSpeed > 0) {
        document.getElementById('modal-speed').value = newSpeed.toFixed(2);
        document.getElementById('modal-speed').dispatchEvent(new Event('input'));
    }
}

function resetToDefault() {
    if (!currentSize || !PIPE_SPEEDS[currentSize]) {
        Swal.fire('Error', 'Please select size first', 'warning');
        return;
    }
    
    const speedData = PIPE_SPEEDS[currentSize];
    document.getElementById('modal-speed').value = speedData.normal;
    document.getElementById('modal-speed').dispatchEvent(new Event('input'));
}

function calculateProductionTime() {
    const qty = parseInt(document.getElementById('modal-qty').value) || 1000;
    const speed = parseFloat(document.getElementById('modal-speed').value) || 2.5;
    
    const minutes = qty / speed;
    const hours = minutes / 60;
    
    let timeString = '';
    if (hours < 1) {
        timeString = Math.ceil(minutes) + ' minutes';
    } else if (hours < 24) {
        const h = Math.floor(hours);
        const m = Math.ceil((hours - h) * 60);
        timeString = h + ' hours ' + m + ' minutes';
    } else {
        const days = Math.floor(hours / 24);
        const h = Math.floor(hours % 24);
        timeString = days + ' days ' + h + ' hours';
    }
    
    document.getElementById('modal-estimated-time').innerHTML = timeString;
    
    const seconds = minutes * 60;
    const completionTime = new Date(Date.now() + (seconds * 1000));
    const completionString = completionTime.toLocaleTimeString([], { 
        hour: '2-digit', 
        minute: '2-digit',
        hour12: false 
    });
    
    const date = completionTime.toDateString() === new Date().toDateString() ? 'Today' : 'Tomorrow';
    document.getElementById('modal-complete-time').innerHTML = `${date} ${completionString}`;
}

document.getElementById('modal-qty')?.addEventListener('input', calculateProductionTime);
document.getElementById('modal-cancel')?.addEventListener('click', hideProductionModal);

// ✅ FIXED: Start Production with optional size
document.getElementById('modal-start')?.addEventListener('click', async () => {
    if (!selectedMachineId) return;
    
    const workOrder = document.getElementById('modal-work-order').value.trim();
    const product = document.getElementById('modal-product').value;
    const color = document.getElementById('modal-color').value;
    const size = document.getElementById('modal-size').value;
    const speed = document.getElementById('modal-speed').value;
    const qty = document.getElementById('modal-qty').value;
    const confirmed = document.getElementById('modal-confirm').checked;
    
    // ✅ Validation - product aur color mandatory
    if (!product || !color) {
        Swal.fire('Error', 'Please select product and color', 'warning');
        return;
    }
    
    // ✅ Size optional - agar nahi select kiya to confirmation
    if (!size) {
        const confirmNoSize = await Swal.fire({
            title: 'No Size Selected?',
            text: 'Are you sure you want to start production without specifying size?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Yes, Continue',
            cancelButtonText: 'Select Size'
        });
        
        if (!confirmNoSize.isConfirmed) {
            return;
        }
    }
    
    if (!confirmed) {
        Swal.fire('Error', 'Please confirm the Work Order details', 'warning');
        return;
    }
    
    // ✅ Final size and speed
    let finalSpeed = speed;
    let sizeForDB = size;
    
    if (!size || size === 'no_size' || size === '') {
        finalSpeed = 2.5;
        sizeForDB = null;
    }
    
    const confirmResult = await Swal.fire({
        title: 'Confirm Production Start',
        html: `
            <div style="text-align: left;">
                <p><strong>Work Order:</strong> ${workOrder}</p>
                <p><strong>Product:</strong> ${product}</p>
                ${currentItemDescription ? `<p><strong>Description:</strong> ${currentItemDescription}</p>` : ''}
                ${currentBrand ? `<p><strong>Brand:</strong> <span style="color:#8b5cf6;">${currentBrand}</span></p>` : ''}
                <p><strong>Color:</strong> ${color}</p>
                <p><strong>Size:</strong> ${sizeForDB || 'Not Specified'}</p>
                <p><strong>Speed:</strong> ${finalSpeed} m/min</p>
                <p><strong>1 Meter:</strong> ${(60 / finalSpeed).toFixed(1)} seconds</p>
                <p><strong>Target:</strong> ${qty} meters</p>
                <p><strong>Estimated Time:</strong> ${document.getElementById('modal-estimated-time').innerHTML}</p>
                <p style="color: #4ade80; margin-top: 10px;">⚙️ CRON will handle updates</p>
            </div>
        `,
        icon: 'question',
        showCancelButton: true,
        confirmButtonColor: '#3b82f6',
        cancelButtonColor: '#475569',
        confirmButtonText: 'Yes, Start Production',
        cancelButtonText: 'Cancel'
    });
    
    if (!confirmResult.isConfirmed) return;
    
    startMachine(selectedMachineId, {
        product: product,
        description: currentItemDescription,
        brand: currentBrand || null,
        customer: window.currentCustomer || null,
        color: color,
        size: sizeForDB,
        speed: finalSpeed,
        qty: qty || 1000,
        work_order: workOrder,
        order_total: window.currentOrderTotal || null
    });
    
    currentItemDescription = '';
    currentBrand = null;
    window.currentCustomer = null;
    window.currentOrderTotal = null;
});

// ========== EDIT MODAL EVENTS ==========
document.getElementById('edit-cancel')?.addEventListener('click', hideEditModal);

document.getElementById('edit-confirm')?.addEventListener('click', async () => {
    if (!editMachineId) return;
    
    const product = document.getElementById('edit-product').value;
    const color = document.getElementById('edit-color').value;
    const size = document.getElementById('edit-size').value;
    const speed = document.getElementById('edit-speed').value;
    
    if (!product || !color) {
        Swal.fire('Error', 'Please select product and color', 'warning');
        return;
    }
    
    // ✅ Size optional in edit mode
    let sizeForDB = size;
    if (!size || size === 'no_size' || size === '') {
        sizeForDB = null;
    }
    
    const success = await updateMachine(editMachineId, {
        product: product,
        color: color,
        size: sizeForDB,
        speed: speed
    });
    
    if (success) {
        hideEditModal();
    }
});

// ========== SPEED MODAL EVENTS ==========
document.getElementById('speed-cancel')?.addEventListener('click', hideSpeedModal);

document.getElementById('speed-confirm')?.addEventListener('click', async () => {
    if (!speedMachineId) return;
    
    const speed = document.getElementById('speed-value').value;
    
    if (!speed || speed <= 0) {
        Swal.fire('Error', 'Please enter valid speed', 'warning');
        return;
    }
    
    const success = await updateSpeed(speedMachineId, speed);
    
    if (success) {
        hideSpeedModal();
    }
});

// ========== STOP MODAL FUNCTIONS ==========
function showStopModal(machineId, machineName) {
    selectedMachineId = machineId;
    selectedReason = '';
    document.getElementById('stop-machine-name').textContent = machineName;
    document.getElementById('stop-confirm').disabled = true;
    
    const reasonList = document.getElementById('reason-list');
    reasonList.innerHTML = '';
    
    STOP_REASONS.forEach(reason => {
        const div = document.createElement('div');
        div.className = 'reason-item';
        div.textContent = reason;
        div.onclick = () => {
            document.querySelectorAll('.reason-item').forEach(el => el.classList.remove('selected'));
            div.classList.add('selected');
            selectedReason = reason;
            document.getElementById('stop-confirm').disabled = false;
        };
        reasonList.appendChild(div);
    });
    
    document.getElementById('stop-modal').classList.remove('hidden');
}

function hideStopModal() {
    document.getElementById('stop-modal').classList.add('hidden');
}

document.getElementById('stop-cancel')?.addEventListener('click', hideStopModal);
document.getElementById('stop-confirm')?.addEventListener('click', () => {
    if (selectedMachineId && selectedReason) {
        stopMachine(selectedMachineId, selectedReason);
    }
});

// ========== WASTE MODAL FUNCTIONS ==========
function showWasteModal(machineId, machineName) {
    selectedWasteMachineId = machineId;
    document.getElementById('waste-machine-name').textContent = machineName;
    document.getElementById('waste-product').value = '';
    document.getElementById('waste-color').value = '';
    document.getElementById('waste-size').value = '';
    document.getElementById('waste-quantity').value = '';
    document.getElementById('waste-notes').value = '';
    document.getElementById('waste-modal').classList.remove('hidden');
}

function hideWasteModal() {
    document.getElementById('waste-modal').classList.add('hidden');
    selectedWasteMachineId = null;
}

document.getElementById('waste-cancel')?.addEventListener('click', hideWasteModal);
document.getElementById('waste-submit')?.addEventListener('click', async () => {
    if (!selectedWasteMachineId) {
        Swal.fire('Error', 'No machine selected', 'error');
        return;
    }
    
    const product = document.getElementById('waste-product').value;
    const color = document.getElementById('waste-color').value;
    const size = document.getElementById('waste-size').value;
    const quantity = document.getElementById('waste-quantity').value;
    const reason = document.getElementById('waste-reason').value;
    const notes = document.getElementById('waste-notes').value;
    
    if (!product || !color) {
        Swal.fire('Error', 'Please select product and color', 'warning');
        return;
    }
    
    if (!quantity || quantity <= 0) {
        Swal.fire('Error', 'Please enter valid quantity', 'warning');
        return;
    }
    
    const machine = machines.find(m => m.machine_id === selectedWasteMachineId);
    if (!machine) {
        Swal.fire('Error', 'Machine not found', 'error');
        return;
    }
    
    Swal.fire({
        title: 'Submitting...',
        text: 'Please wait',
        allowOutsideClick: false,
        didOpen: () => {
            Swal.showLoading();
        }
    });
    
    const result = await reportWaste({
        machine_id: selectedWasteMachineId,
        machine_name: machine.name || '',
        location_name: machine.location_name || '',
        product: product,
        color: color,
        size: size === 'no_size' ? null : size,
        quantity: quantity,
        reason: reason,
        notes: notes
    });
    
    Swal.close();
    
    if (result.success) {
        showNotification('✅ Waste reported');
        hideWasteModal();
        await loadWasteReport();
        updateSummaryWidgets();
    } else {
        Swal.fire('Error', result.message || 'Failed to report waste', 'error');
    }
});

// ========== TIME FUNCTIONS ==========
function formatTime(seconds) {
    if (seconds < 60) return `${Math.ceil(seconds)} sec`;
    if (seconds < 3600) return `${Math.ceil(seconds/60)} min`;
    
    const hours = Math.floor(seconds / 3600);
    const mins = Math.ceil((seconds % 3600) / 60);
    
    if (hours >= 24) {
        const days = Math.floor(hours / 24);
        const remainingHours = hours % 24;
        return `${days}d ${remainingHours}h ${mins}m`;
    }
    
    return `${hours}h ${mins}m`;
}

function calculateRemainingTime(machine) {
    if (machine.status !== 'running') return '—';
    
    const remaining = (machine.target_qty || 0) - (machine.produced_qty || 0);
    if (remaining <= 0) return 'Complete';
    
    const speed = machine.current_speed || 2.5;
    const minutesRemaining = remaining / speed;
    
    return formatTime(minutesRemaining * 60);
}

function calculateCompleteTime(machine) {
    if (machine.status !== 'running') return '—';
    
    const remaining = (machine.target_qty || 0) - (machine.produced_qty || 0);
    if (remaining <= 0) return 'Now';
    
    const speed = machine.current_speed || 2.5;
    const minutesRemaining = remaining / speed;
    const secondsRemaining = minutesRemaining * 60;
    
    const completionTime = new Date(Date.now() + (secondsRemaining * 1000));
    const timeStr = completionTime.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    const dateStr = completionTime.toDateString() === new Date().toDateString() ? 'Today' : 'Tomorrow';
    
    return `${dateStr} ${timeStr}`;
}

// ========== SALES ORDER SEARCH MODAL - UPDATED with Order Total ==========
function showSalesOrderSearch(machineId, machineName) {
    selectedMachineId = machineId;
    
    const machine = machines.find(m => m.machine_id === machineId);
    const machineLocation = machine ? machine.location_name : '';
    
    Swal.fire({
        title: 'Search Sales Order',
        html: `
            <div>
                <div style="margin-bottom: 15px;">
                    <input type="text" id="so-search-input" 
                           style="width:100%; padding:12px; border-radius:30px; border:2px solid #3b82f6; background:#0f172a; color:white; font-size:1rem;"
                           placeholder="🔍 Type order ID or item name...">
                </div>
                
                <div id="so-search-results" style="max-height: 400px; overflow-y: auto; padding:5px;"></div>
                
                <div style="margin-top:15px; color:#3b82f6; font-size:0.8rem; text-align:center; background:#2d3f55; padding:8px; border-radius:30px;">
                    📍 Showing orders for: <strong>${machineLocation}</strong>
                </div>
            </div>
        `,
        showConfirmButton: false,
        showCancelButton: true,
        cancelButtonText: '❌ Close',
        background: '#1e293b',
        color: 'white',
        width: '600px',
        
        didOpen: () => {
            const searchInput = document.getElementById('so-search-input');
            const resultsDiv = document.getElementById('so-search-results');
            
            searchInput.focus();
            
            let searchTimeout;
            
            searchInput.addEventListener('input', async function() {
                const term = this.value.trim();
                
                clearTimeout(searchTimeout);
                
                if (term.length < 2) {
                    resultsDiv.innerHTML = '<div style="text-align:center; padding:30px; color:#94a3b8;">🔍 Type at least 2 characters to search</div>';
                    return;
                }
                
                resultsDiv.innerHTML = '<div style="text-align:center; padding:30px;"><div class="loading"></div><p style="color:#94a3b8;">Searching...</p></div>';
                
                searchTimeout = setTimeout(async () => {
                    try {
                        const res = await fetch('/production/api.php?action=fetch_sales_order', {
                            method: 'POST',
                            headers: {'Content-Type': 'application/json'},
                            body: JSON.stringify({
                                search_term: term,
                                location: machineLocation
                            })
                        });
                        
                        const data = await res.json();
                        
                        if (data.data && data.data.length > 0) {
                            let html = '';
                            
                            data.data.forEach(order => {
                                const orderId = order.name;
                                const customerName = order.customer_name || '';
                                
                                // Calculate order total properly
                                let orderTotal = 0;
                                if (order.items && order.items.length > 0) {
                                    order.items.forEach(item => {
                                        orderTotal += item.qty || 0;
                                    });
                                }
                                
                                html += `
                                    <div data-order-id="${orderId}" style="margin-bottom: 20px; border: 1px solid #3b82f6; border-radius: 8px; padding: 15px; background: #0f172a;">
                                        <div style="font-weight: bold; color: #3b82f6; font-size: 1rem; margin-bottom: 12px; border-bottom: 1px solid #334155; padding-bottom: 8px;">
                                            📋 ${order.name}
                                            ${customerName ? `<span style="display: block; font-size: 0.8rem; color: #94a3b8; margin-top: 4px;">👤 ${customerName}</span>` : ''}
                                            <!-- ORDER TOTAL BADGE - Fixed -->
                                            <div style="background: #10b981; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.8rem; display: inline-block; margin-top: 8px; font-weight: bold;">
                                                📊 Order Total: ${orderTotal}m
                                            </div>
                                        </div>
                                `;
                                
                                if (order.items && order.items.length > 0) {
                                    order.items.forEach((item, idx) => {
                                        const itemName = item.item_name || item.item_code || 'Unknown Item';
                                        const itemQty = item.qty || 0;
                                        
                                        // Check unit - if meter show "m" else just number
                                        const unitText = item.uom === 'Meter' ? 'm' : (item.uom || '');
                                        const qtyDisplay = unitText ? `${itemQty}${unitText}` : `${itemQty}`;
                                        
                                        html += `
                                            <div onclick="selectItemFromOrderWithContext('${orderId}', ${idx}, ${orderTotal})" 
                                                 style="padding: 15px; border: 1px solid #334155; border-radius: 8px; margin-bottom: 10px; cursor: pointer; background: #1e293b; transition: all 0.2s;"
                                                 onmouseover="this.style.background='#2d3f55'; this.style.borderColor='#8b5cf6';"
                                                 onmouseout="this.style.background='#1e293b'; this.style.borderColor='#334155';">
                                                <div style="font-weight: 600; color: #f1f5f9; font-size: 1rem; margin-bottom: 10px;">
                                                    ${itemName}
                                                </div>
                                                
                                                <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 10px;">
                                                    <span style="color: #94a3b8;">Brand:</span>
                                                    ${item.brand ? `
                                                        <span style="background: #8b5cf6; color: white; padding: 6px 20px; border-radius: 30px; font-size: 0.9rem; font-weight: 700;">
                                                            🏷️ ${item.brand}
                                                        </span>
                                                    ` : `
                                                        <span style="background: #475569; color: #94a3b8; padding: 6px 20px; border-radius: 30px; font-size: 0.9rem;">
                                                            No Brand
                                                        </span>
                                                    `}
                                                </div>
                                                
                                                <div style="display: flex; justify-content: space-between; color: #f59e0b; font-size: 0.9rem;">
                                                    <span>📦 Quantity: ${qtyDisplay}</span>
                                                    <span>📊 Order Total: ${orderTotal}m</span>
                                                </div>
                                            </div>
                                        `;
                                    });
                                } else {
                                    html += `<div style="color: #94a3b8; padding: 15px; text-align:center;">No items in this order</div>`;
                                }
                                html += '</div>';
                            });
                            
                            resultsDiv.innerHTML = html;
                            window.allSalesOrders = data.data;
                            
                        } else {
                            resultsDiv.innerHTML = '<div style="text-align:center; padding:40px; color:#94a3b8;">No orders found</div>';
                        }
                    } catch (error) {
                        resultsDiv.innerHTML = '<div style="text-align:center; padding:40px; color:#ef4444;">Error: ' + error.message + '</div>';
                    }
                }, 300);
            });
        }
    });
}

// ========== HELPER FUNCTION TO FIND ORDER BY ID ==========
function selectItemFromOrderWithContext(orderId, itemIndex, orderTotal) {
    if (!window.allSalesOrders) {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'No order data available',
            background: '#1e293b',
            color: 'white'
        });
        return;
    }
    
    const order = window.allSalesOrders.find(o => o.name === orderId);
    if (order) {
        window.currentSalesOrder = order;
        window.currentOrderTotal = orderTotal;
        const item = order.items[itemIndex];
        selectItemFromOrder(orderId, itemIndex, item.brand, order.customer_name, orderTotal);
    } else {
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: 'Order not found',
            background: '#1e293b',
            color: 'white'
        });
    }
}

// ========== SELECT AND LOAD SALES ORDER ==========
async function selectSalesOrder(orderId) {
    Swal.fire({
        title: 'Loading Order...',
        html: '<div class="loading" style="margin:20px auto;"></div>',
        allowOutsideClick: false,
        showConfirmButton: false,
        background: '#1e293b',
        color: 'white'
    });
    
    try {
        const res = await fetch(`${API_URL}?action=get_sales_order_details`, {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({order_id: orderId})
        });
        
        const data = await res.json();
        Swal.close();
        
        if (data.success && data.data) {
            const order = data.data;
            
            if (order.items && order.items.length > 0) {
                let itemsHtml = '<div style="max-height: 300px; overflow-y: auto;">';
                let orderTotal = 0;
                order.items.forEach(item => {
                    orderTotal += item.qty || 0;
                });
                
                order.items.forEach((item, index) => {
                    const brand = item.brand ? ` [${item.brand}]` : '';
                    itemsHtml += `
                        <div onclick="selectItemFromOrder('${order.name}', ${index}, '${item.brand || ''}', '${order.customer_name || ''}', ${orderTotal})" 
                             style="padding: 12px; border: 1px solid #3b82f6; border-radius: 8px; 
                                    margin-bottom: 8px; cursor: pointer; background: #0f172a;
                                    transition: all 0.2s;"
                             onmouseover="this.style.background='#2d3f55'"
                             onmouseout="this.style.background='#0f172a'">
                            
                            <div style="font-weight: bold; color: #3b82f6; font-size: 0.9rem;">
                                ${item.item_name || item.item_code}${brand}
                            </div>
                            
                            ${item.description ? `
                                <div style="color: #94a3b8; font-size: 0.75rem; margin-top: 2px; line-height: 1.4;">
                                    ${item.description}
                                </div>
                            ` : ''}
                            
                            <div style="display: flex; justify-content: space-between; margin-top: 8px; color: #94a3b8; font-size: 0.8rem;">
                                <span>📦 Qty: ${item.qty}</span>
                                <span>📊 Total: ${orderTotal}m</span>
                            </div>
                        </div>
                    `;
                });
                itemsHtml += '</div>';
                
                window.currentSalesOrder = order;
                window.currentOrderTotal = orderTotal;
                
                Swal.fire({
                    title: order.items.length === 1 ? 'Confirm Item' : 'Select Item',
                    html: itemsHtml,
                    showConfirmButton: false,
                    showCancelButton: true,
                    cancelButtonText: 'Cancel',
                    background: '#1e293b',
                    color: 'white',
                    width: '600px'
                });
                
            } else {
                document.getElementById('modal-work-order').value = order.name;
                document.getElementById('production-modal').classList.remove('hidden');
                showNotification('Order loaded (no items)', 'info');
            }
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Failed to load order',
                background: '#1e293b',
                color: 'white'
            });
        }
    } catch (error) {
        Swal.close();
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            background: '#1e293b',
            color: 'white'
        });
    }
}

// ========== ENHANCED PRODUCT MAPPING - UPDATED with MICRO DUCT detection ==========
function mapERPItemToLocal(erpItemName, description = '') {
    if (!erpItemName && !description) return { product: '', color: '', size: '', fullDescription: description };
    
    const itemName = (erpItemName || '').toLowerCase().trim();
    const descText = (description || '').toLowerCase().trim();
    const combinedText = itemName + ' ' + descText;
    
    const extractColor = (text) => {
        if (!text) return '';
        const textLower = text.toLowerCase();
        const colorPriority = ['orange', 'blue', 'grey', 'gray', 'green', 'red', 'white', 'yellow', 'brown', 'black'];
        for (let color of colorPriority) {
            if (textLower.includes(color)) {
                if (color === 'gray') return 'Grey';
                return color.charAt(0).toUpperCase() + color.slice(1);
            }
        }
        return '';
    };
    
    const extractSize = (text) => {
        if (!text) return '';
        const sizeMatch = text.match(/(\d+)\s*mm/);
        if (sizeMatch) return sizeMatch[1] + 'mm';
        if (text.includes('20/16')) return '16mm';
        if (text.includes('12/8')) return '12mm';
        if (text.includes('25/20')) return '20mm';
        return '';
    };
    
    // ✅ MICRO DUCT 7 WAY 25/20mm detection
    if (combinedText.includes('microduct7way25/20') || 
        (combinedText.includes('microduct') && combinedText.includes('7way') && combinedText.includes('25/20'))) {
        return {
            product: 'MICRO DUCT 7 WAY 25/20 MM',
            color: extractColor(descText),
            size: '25mm',
            fullDescription: description
        };
    }
    
    // ✅ MICRO DUCT 7 WAY 20/16mm detection
    if (combinedText.includes('microduct7way20/16') || 
        (combinedText.includes('microduct') && combinedText.includes('7way') && combinedText.includes('20/16'))) {
        return {
            product: 'MICRO DUCT 7 WAY 20/16',
            color: extractColor(descText),
            size: '20mm',
            fullDescription: description
        };
    }
    
    // ✅ MICRO DUCT 2 WAY 12/8mm detection
    if (combinedText.includes('microduct2way12/8') || 
        (combinedText.includes('microduct') && combinedText.includes('2way') && combinedText.includes('12/8'))) {
        return {
            product: 'MICRO DUCT 2 WAY 12/8 MM',
            color: extractColor(descText),
            size: '12mm',
            fullDescription: description
        };
    }
    
    if (combinedText.includes('hdpepipe20') || (combinedText.includes('hdpe') && combinedText.includes('20') && !combinedText.includes('micro'))) {
        return {
            product: 'HDPE 20 X 1.9 MINI DUCT',
            color: extractColor(descText),
            size: '20mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('hdpepipe32') || (combinedText.includes('hdpe') && combinedText.includes('32') && !combinedText.includes('micro'))) {
        return {
            product: 'HDPE 32 X 1.9 MINI DUCT',
            color: extractColor(descText),
            size: '32mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('hdpepipe50') || (combinedText.includes('hdpe') && combinedText.includes('50') && !combinedText.includes('micro'))) {
        return {
            product: 'HDPE 50 X 44 MINI DUCT',
            color: extractColor(descText),
            size: '50mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('hdpecd110') && combinedText.includes('4subduct')) {
        return {
            product: 'HDPE-CD 110 MM 4X38 MM',
            color: extractColor(descText),
            size: '110mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('hdpecd110') && combinedText.includes('5x33')) {
        return {
            product: 'HDPE-CD 110 MM 5X33 MM',
            color: extractColor(descText),
            size: '110mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('hdpecdempty110')) {
        return {
            product: 'HDPE-CD EMPTY 110 MM',
            color: extractColor(descText),
            size: '110mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('microduct16way20/16')) {
        return {
            product: 'MICRO DUCT 16 WAY 20/16 MM',
            color: extractColor(descText),
            size: '20mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('microduct12way12/8')) {
        return {
            product: 'MICRO DUCT 12 WAY 12/8 MM',
            color: extractColor(descText),
            size: '12mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('microduct4way25/20')) {
        return {
            product: 'MICRO DUCT 4 WAY 25/20MM',
            color: extractColor(descText),
            size: '25mm',
            fullDescription: description
        };
    }
    if (combinedText.includes('microduct4wayflat')) {
        return {
            product: 'MICRO DUCT 4 WAY 20/16MM flat',
            color: extractColor(descText),
            size: '20mm',
            fullDescription: description
        };
    }
    
    return {
        product: '',
        color: extractColor(descText),
        size: extractSize(descText) || extractSize(itemName),
        fullDescription: description
    };
}

// ========== SELECT ITEM FROM ORDER - FINAL WITH BRAND AND CUSTOMER ==========
async function selectItemFromOrder(orderId, itemIndex, brandName = '', customerName = '', orderTotal = 0) {
    try {
        const order = window.currentSalesOrder;
        if (!order || !order.items || !order.items[itemIndex]) return;
        
        const item = order.items[itemIndex];
        
        document.getElementById('modal-work-order').value = orderId;
        
        currentItemDescription = item.description || '';
        currentBrand = brandName || null;
        window.currentCustomer = customerName || null;
        window.currentOrderTotal = orderTotal || item.qty || 0;
        
        trackOrderDistribution(orderId, order.items, orderTotal);
        
        if (currentItemDescription || currentBrand || customerName) {
            const descDiv = document.getElementById('modal-description-preview');
            descDiv.style.display = 'block';
            
            let html = '';
            if (customerName && currentUser?.role === 'manager') {
                html += `
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="background: #3b82f6; color: white; padding: 4px 15px; border-radius: 30px; font-weight:600;">
                            👤 ${customerName}
                        </span>
                    </div>
                `;
            }
            if (currentBrand) {
                html += `
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 8px;">
                        <span style="background: #8b5cf6; color: white; padding: 4px 15px; border-radius: 30px; font-weight:600;">
                            🏷️ ${currentBrand}
                        </span>
                    </div>
                `;
            }
            if (currentItemDescription) {
                html += `<div>${currentItemDescription}</div>`;
            }
            
            // Show Order Total in preview
            if (orderTotal > 0) {
                html += `<div style="background: #10b981; color: white; padding: 4px 12px; border-radius: 30px; font-size: 0.8rem; display: inline-block; margin-top: 8px; font-weight: bold;">📊 Order Total: ${orderTotal}m</div>`;
            }
            
            descDiv.innerHTML = html;
            descDiv.style.cssText = 'background: #2d3f55; padding: 12px; border-radius: 8px; margin: 10px 0; color: #94a3b8; font-size: 0.8rem; border-left: 3px solid #8b5cf6;';
        }
        
        const mappedData = mapERPItemToLocal(
            item.item_name || item.item_code || '', 
            item.description || ''
        );
        
        if (mappedData.product) {
            document.getElementById('modal-product').value = mappedData.product;
        }
        
        if (mappedData.color) {
            document.getElementById('modal-color').value = mappedData.color;
        }
        
        if (mappedData.size) {
            document.getElementById('modal-size').value = mappedData.size;
            if (PIPE_SPEEDS[mappedData.size] && document.getElementById('auto-speed-toggle').checked) {
                document.getElementById('modal-speed').value = PIPE_SPEEDS[mappedData.size].normal;
                document.getElementById('size-info').innerHTML = 
                    `✅ ${mappedData.size} pipe: ${PIPE_SPEEDS[mappedData.size].time.toFixed(1)} sec/meter`;
                updateRateDisplay();
            }
        }
        
        // Set quantity with proper unit
        const itemQty = item.qty || 1000;
        document.getElementById('modal-qty').value = itemQty;
        
        Swal.close();
        
        // Show unit in confirmation message
        const unitText = item.uom === 'Meter' ? 'm' : (item.uom || '');
        const qtyDisplay = unitText ? `${itemQty}${unitText}` : `${itemQty}`;
        
        Swal.fire({
            icon: 'success',
            title: '✅ Order Loaded',
            html: `
                <div style="text-align: left;">
                    <p><strong>Order:</strong> ${orderId}</p>
                    ${customerName ? `<p><strong>Customer:</strong> <span style="color:#3b82f6;">${customerName}</span></p>` : ''}
                    <p><strong>Product:</strong> ${document.getElementById('modal-product').value}</p>
                    ${brandName ? `<p><strong>Brand:</strong> <span style="color:#8b5cf6;">${brandName}</span></p>` : ''}
                    <p><strong>Quantity:</strong> ${qtyDisplay}</p>
                    <p><strong>Order Total:</strong> ${orderTotal}m</p>
                    <p><strong>Speed:</strong> ${document.getElementById('modal-speed').value} m/min</p>
                    <p><strong>1 Meter:</strong> ${(60 / document.getElementById('modal-speed').value).toFixed(1)} seconds</p>
                    <p style="color: #4ade80; margin-top: 10px;">⚙️ CRON will handle updates</p>
                </div>
            `,
            timer: 5000,
            showConfirmButton: false,
            background: '#1e293b',
            color: 'white'
        });
        
        document.getElementById('production-modal').classList.remove('hidden');
        calculateProductionTime();
        
    } catch (error) {
        console.error('Error:', error);
        Swal.fire({
            icon: 'error',
            title: 'Error',
            text: error.message,
            background: '#1e293b',
            color: 'white'
        });
    }
}

/// ========== RENDER DASHBOARD WITH TIMESTAMPS ==========
function renderDashboard() {
    if (!currentUser || !machines.length) {
        document.getElementById('locations').innerHTML = '<div style="text-align:center; padding:2rem;">No machines</div>';
        return;
    }
    
    const locFilter = document.getElementById('filter-location')?.value || 'all';
    const statusFilter = document.getElementById('filter-status')?.value || 'all';
    // ✅ FIX: search field ka value sirf tab lo jab user ne khud type kiya ho
    const fsEl = document.getElementById('filter-search');
    const searchFilter = (fsEl && fsEl.dataset.userTyped === '1') ? fsEl.value.toLowerCase() : '';
    
    const locMap = {};
    machines.forEach(m => {
        if (!locMap[m.location_name]) locMap[m.location_name] = [];
        if (locFilter !== 'all' && m.location_name !== locFilter) return;
        if (statusFilter !== 'all' && m.status !== statusFilter) return;
        if (searchFilter && !m.name.toLowerCase().includes(searchFilter) && 
            !(m.current_product && m.current_product.toLowerCase().includes(searchFilter))) return;
        locMap[m.location_name].push(m);
    });
    
    let html = '';
    const now = new Date();
    
    for (const [loc, ms] of Object.entries(locMap)) {
        let machinesHtml = '';
        ms.forEach(m => {
            const isOperator = currentUser.role === 'operator';
            const status = m.status;
            const target = m.target_qty || 0;
            const produced = m.produced_qty || 0;
            
            let percent = 0;
            if (target > 0) {
                percent = Math.min(100, Math.round((produced / target) * 100));
            }
            
            const speed = m.current_speed || 2.5;
            const pipeInfo = PIPE_SPEEDS[m.current_size];
            const rate = calculateProductionRate(speed);
            
            // CALCULATE LAST UPDATE TIME
            let lastUpdateTime = 'Unknown';
            let secondsSinceUpdate = 999;
            if (m.last_updated) {
                const lastUpdate = new Date(m.last_updated);
                secondsSinceUpdate = Math.round((now - lastUpdate) / 1000);
                lastUpdateTime = secondsSinceUpdate + 's ago';
            }
            
            machinesHtml += `
                <div class="machine-card" data-id="${m.machine_id}">
                    <div class="machine-header">
                        <span class="machine-name">${m.name}</span>
                        <span class="machine-id">${m.machine_id}</span>
                        <!-- RENAME BUTTON - Sirf manager ko dikhe -->
                        ${currentUser.role === 'manager' ? `
                            <button onclick="showRenameModal('${m.machine_id}', '${m.name}')" 
                                    style="background: #3b82f6; border: none; color: white; width: 30px; height: 30px; border-radius: 50%; cursor: pointer; font-size: 1rem; margin-left: 5px;" 
                                    title="Rename Machine">✏️</button>
                        ` : ''}
                    </div>
                    <span class="status-badge status-${status}">${status === 'idle' ? 'FREE' : status.toUpperCase()}</span>
                    
                    ${m.current_product ? `
                        <!-- STOCK badge show -->
                        ${m.work_order ? `
                            <div class="order-badge" title="Work Order" 
                                 style="${m.work_order === 'STOCK' ? 'background: #64748b; border-color: #475569;' : ''}">
                                📋 ${m.work_order}
                            </div>
                        ` : ''}
                        
                        <!-- CUSTOMER NAME - SIRF MANAGER KO DIKHE -->
                        ${currentUser.role === 'manager' && m.current_customer ? `
                            <div class="customer-badge">
                                👤 ${m.current_customer}
                            </div>
                        ` : ''}
                        
                        <!-- BRAND DISPLAY - SABKO DIKHE -->
                        ${m.current_brand ? `
                            <div class="brand-badge">
                                🏷️ ${m.current_brand}
                            </div>
                        ` : ''}
                        
                        <div class="product-item-name">${m.current_product}</div>
                        
                        ${m.current_description ? `
                            <div class="description-box">
                                📝 ${m.current_description}
                            </div>
                        ` : ''}
                        
                        <div class="product-details-row">
                            <span class="product-size-badge">${m.current_size || '-'}</span>
                            <span class="product-color-display">
                                <span class="color-dot" style="background: ${colorMap[m.current_color] || '#94a3b8'}"></span>
                                <span>${m.current_color || '-'}</span>
                            </span>
                        </div>
                        
                        <!-- ORDER PROGRESS - sirf NON-STOCK orders ke liye -->
                        ${m.work_order && m.work_order !== 'STOCK' && selectedOrderItems[m.work_order] ? `
                            <div class="order-progress">
                                <div class="order-progress-header">
                                    <span class="order-progress-label">📊 Order Progress:</span>
                                    <span class="order-progress-value" id="order-remaining-${m.work_order}">
                                        ${(selectedOrderItems[m.work_order].totalQty - selectedOrderItems[m.work_order].allocatedQty).toLocaleString()}m remaining
                                    </span>
                                </div>
                                <div class="progress-bar" style="height: 6px;">
                                    <div class="progress-fill" id="order-progress-${m.work_order}" 
                                         style="width: ${(selectedOrderItems[m.work_order].allocatedQty / selectedOrderItems[m.work_order].totalQty * 100)}%;"></div>
                                </div>
                            </div>
                        ` : ''}
                        
                        <div class="speed-indicator">
                            <div class="speed-row">
                                <span style="color: #4f46e5; font-weight: 600;">⚡ Speed:</span>
                                <span class="speed-value">${speed} m/min</span>
                                <button class="speed-edit-btn" onclick="showSpeedModal('${m.machine_id}', ${speed})" title="Change Speed">✏️</button>
                            </div>
                            
                            <!-- Production Rate Display -->
                            <div class="production-rate">
                                <span>⏱️ 1 Meter =</span>
                                <span class="rate-value">${rate.secondsPerMeter} sec</span>
                                <span>|</span>
                                <span>📏 Per Min =</span>
                                <span class="rate-value">${rate.metersPerMinute} m</span>
                            </div>
                            
                            ${pipeInfo ? `<div class="size-info-badge">${pipeInfo.time.toFixed(1)} sec/m (standard)</div>` : ''}
                        </div>
                        
                        <div class="quantity-display">
                            <span class="target-qty">🎯 ${target}m</span>
                            <span class="produced-qty">✅ ${produced}m</span>
                        </div>
                        
                        ${status === 'running' ? `
                        <div class="time-panel">
                            <div class="time-item">
                                <div class="time-label">Remaining</div>
                                <div class="time-value" id="remaining-${m.machine_id}">${calculateRemainingTime(m)}</div>
                            </div>
                            <div class="time-item">
                                <div class="time-label">Complete at</div>
                                <div class="time-value" id="complete-${m.machine_id}">${calculateCompleteTime(m)}</div>
                            </div>
                        </div>
                        ` : ''}
                        
                        <div class="progress-container">
                            <div class="progress-header">
                                <span>Progress</span>
                                <span style="font-weight: 600; color: ${percent > 0 ? '#f59e0b' : '#94a3b8'};">${percent}%</span>
                            </div>
                            <div class="progress-bar">
                                <div class="progress-fill ${percent >= 100 ? 'complete' : ''}" 
                                     style="width: ${percent}%;"></div>
                            </div>
                        </div>
                        
                        <!-- TIMESTAMP -->
                        <div style="font-size:0.6rem; color:#64748b; text-align:right; margin-top:8px; border-top:1px dashed #e2e8f0; padding-top:5px;">
                            ⏱️ DB updated: ${lastUpdateTime}
                            ${secondsSinceUpdate < 10 ? '<span style="background:#4ade80; color:#0f172a; padding:2px 8px; border-radius:20px; font-size:0.6rem; font-weight:600; margin-left:5px;">JUST UPDATED</span>' : ''}
                        </div>
                        
                        <div style="font-size:0.55rem; color:#4ade80; text-align:center; margin-top:3px;">
                            ⚙️ CRON updates every 60s
                        </div>
                    ` : ''}
                    
                    ${m.status === 'completed' && m.completed_at ? `
                        <div class="completed-message">✓ Completed at ${new Date(m.completed_at).toLocaleTimeString()}</div>
                        <button class="reset-btn" onclick="resetMachine('${m.machine_id}')">New Order</button>
                    ` : ''}
                    
                    ${m.status === 'stopped' && m.stop_reason ? `
                        <div class="stop-reason-display">⛔ ${m.stop_reason}</div>
                    ` : ''}
                    
                    ${isOperator && status === 'idle' ? `
                        <div style="display: flex; gap: 8px; margin-top: 10px;">
                            <button class="start-btn" onclick="showProductionModal('${m.machine_id}', '${m.name}')" 
                                    style="flex: 1; background: linear-gradient(135deg, #3b82f6, #2563eb);">
                                Manual
                            </button>
                            <button class="btn" onclick="showSalesOrderSearch('${m.machine_id}', '${m.name}')" 
                                    style="flex: 1; background: linear-gradient(135deg, #8b5cf6, #7c3aed); padding: 0.7rem; border: none; border-radius: 40px; color: white; font-weight: 700; font-size: 0.7rem; display: flex; align-items: center; justify-content: center; gap: 5px;">
                                Load Order
                            </button>
                        </div>
                    ` : ''}
                    
                    ${isOperator && status === 'running' ? `
                        <div class="update-row">
                            <span class="update-label">Prod:</span>
                            <button class="qty-btn-small" onclick="adjustQuantity('${m.machine_id}', -10)">−</button>
                            <input class="qty-input-small" id="qty-${m.machine_id}" value="${Math.round(produced)}" min="0" max="${target}" step="10">
                            <button class="qty-btn-small" onclick="adjustQuantity('${m.machine_id}', 10)">+</button>
                            <button class="small-btn" onclick="updateQuantityFromInput('${m.machine_id}')">Set</button>
                        </div>
                        <div class="update-row">
                            <span class="update-label">Target:</span>
                            <button class="qty-btn-small" onclick="adjustTarget('${m.machine_id}', -100)">−</button>
                            <input class="qty-input-small" id="target-${m.machine_id}" value="${target}" min="${produced}" step="100">
                            <button class="qty-btn-small" onclick="adjustTarget('${m.machine_id}', 100)">+</button>
                            <button class="small-btn" onclick="updateTargetFromInput('${m.machine_id}')">Set</button>
                        </div>
                        <button class="waste-btn" onclick="showWasteModal('${m.machine_id}', '${m.name}')">
                            ⚠️ Report Waste
                        </button>
                        
                        <div class="action-buttons">
                            <button class="card-btn edit-btn" onclick="showEditModal('${m.machine_id}')">✏️ Edit</button>
                            <button class="card-btn stop-btn" onclick="showStopModal('${m.machine_id}', '${m.name}')">STOP</button>
                            <button class="card-btn complete-btn" onclick="completeMachine('${m.machine_id}')">DONE</button>
                        </div>
                    ` : ''}
                    
                    ${isOperator && status === 'stopped' ? `
                        <div class="action-buttons">
                            <button class="card-btn edit-btn" onclick="showEditModal('${m.machine_id}')">✏️ Edit</button>
                            <button class="card-btn resume-btn" onclick="resumeMachine('${m.machine_id}')">RESUME</button>
                            <button class="card-btn complete-btn" onclick="completeMachine('${m.machine_id}')">DONE</button>
                        </div>
                    ` : ''}
                </div>
            `;
        });
        
        if (machinesHtml) {
            html += `<div class="location-card"><div class="location-title">📍 ${loc}</div><div class="machines-grid">${machinesHtml}</div></div>`;
        }
    }
    
    document.getElementById('locations').innerHTML = html || '<div style="text-align:center; padding:2rem;">No machines match filters</div>';
}

// ========== UPDATE FUNCTIONS ==========
window.adjustQuantity = function(id, change) {
    const input = document.getElementById(`qty-${id}`);
    if (!input) return;
    
    let newVal = parseInt(input.value) + change;
    const max = parseInt(input.max);
    const min = parseInt(input.min) || 0;
    
    if (newVal > max) newVal = max;
    if (newVal < min) newVal = min;
    
    input.value = newVal;
    updateProducedQuantity(id, newVal);
}

window.updateQuantityFromInput = function(id) {
    const input = document.getElementById(`qty-${id}`);
    if (input) updateProducedQuantity(id, parseInt(input.value));
}

window.adjustTarget = function(id, change) {
    const input = document.getElementById(`target-${id}`);
    if (!input) return;
    
    let newVal = parseInt(input.value) + change;
    const min = parseInt(input.min);
    
    if (newVal < min) newVal = min;
    
    input.value = newVal;
    updateTargetQuantity(id, newVal);
}

window.updateTargetFromInput = function(id) {
    const input = document.getElementById(`target-${id}`);
    if (input) updateTargetQuantity(id, parseInt(input.value));
}

// ========== DOWNTIME REPORT ==========
document.getElementById('btn-view-downtime')?.addEventListener('click', async () => {
    await loadDowntimeReport('today');
    document.getElementById('downtime-modal').classList.remove('hidden');
});

document.querySelectorAll('.filter-btn').forEach(btn => {
    btn.addEventListener('click', async (e) => {
        document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
        e.target.classList.add('active');
        await loadDowntimeReport(e.target.dataset.filter);
    });
});

async function loadDowntimeReport(filter = 'today') {
    const tbody = document.getElementById('downtime-table-body');
    tbody.innerHTML = '<tr><td colspan="8" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>';
    
    try {
        const res = await fetch(`${API_URL}?action=get_downtime_report&filter=${filter}`);
        const data = await res.json();
        downtimeData = data.data || [];
        
        let rows = '', total = 0, active = 0;
        if (downtimeData.length === 0) {
            rows = '<tr><td colspan="8" style="text-align:center;">No records</td></tr>';
        } else {
            downtimeData.forEach(d => {
                total += parseInt(d.downtime_minutes) || 0;
                if (d.status === 'active') active++;
                const deleteBtn = currentUser?.role === 'manager' 
                    ? `<button class="delete-btn" onclick="deleteDowntimeRecord(${d.id})">Delete</button>`
                    : '-';
                rows += `<tr>
                    <td>${d.machine_name} (${d.machine_id})</td>
                    <td>${d.location_name || '-'}</td>
                    <td style="color:#ef4444;">${d.stop_reason || '-'}</td>
                    <td>${formatDateTime(d.stopped_at)}</td>
                    <td>${d.resumed_at ? formatDateTime(d.resumed_at) : '-'}</td>
                    <td>${d.downtime_minutes || (d.status === 'active' ? 'Ongoing' : '-')}</td>
                    <td><span style="background:${d.status === 'active' ? '#ef4444' : '#10b981'}; padding:0.2rem 0.5rem; border-radius:30px;">${d.status}</span></td>
                    <td>${deleteBtn}</td>
                </tr>`;
            });
        }
        tbody.innerHTML = rows;
        document.getElementById('total-downtime').textContent = total + ' min';
        document.getElementById('active-issues').textContent = active;
        document.getElementById('avg-downtime').textContent = downtimeData.length ? Math.round(total/downtimeData.length) + ' min' : '0';
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="8" style="color:#ef4444; text-align:center;">Error loading data</td></tr>';
    }
}

document.getElementById('btn-export-downtime')?.addEventListener('click', () => {
    if (!downtimeData.length) {
        Swal.fire('Info', 'No data to export', 'info');
        return;
    }
    let csv = "Machine,Location,Reason,Stopped,Resumed,Minutes,Status\n";
    downtimeData.forEach(d => {
        csv += `"${d.machine_name}","${d.location_name}","${d.stop_reason}","${d.stopped_at}","${d.resumed_at}","${d.downtime_minutes}","${d.status}"\n`;
    });
    const blob = new Blob(["\uFEFF" + csv], {type: 'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `downtime_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
});

// ========== METRICS REPORT ==========
document.getElementById('btn-view-metrics')?.addEventListener('click', async () => {
    await loadMetricsReport();
    document.getElementById('metrics-modal').classList.remove('hidden');
});

async function loadMetricsReport() {
    const tbody = document.getElementById('metrics-table-body');
    tbody.innerHTML = '<tr><td colspan="13" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>';
    
    try {
        const res = await fetch(`${API_URL}?action=get_completed_orders`);
        const data = await res.json();
        completedOrders = data.data || [];
        
        let rows = '';
        if (!completedOrders.length) {
            rows = '<tr><td colspan="13" style="text-align:center;">No completed orders</td></tr>';
        } else {
            const seen = new Set();
            completedOrders.forEach(o => {
                const key = `${o.machine_id}_${o.completed_at}`;
                if (seen.has(key)) return;
                seen.add(key);
                const deleteBtn = currentUser?.role === 'manager' 
                    ? `<button class="delete-btn" onclick="deleteOrder(${o.id})">Delete</button>`
                    : '-';
                rows += `<tr>
                    <td>${o.location_name || '-'}</td>
                    <td>${o.machine_name || '-'}</td>
                    <td style="font-weight:600; color:#8b5cf6;">${o.work_order || '-'}</td>
                    <td>${o.product || '-'}</td>
                    <td>${o.brand || '-'}</td>
                    <td>${o.customer_name || '-'}</td>
                    <td style="max-width: 200px; color: #94a3b8;">${o.description || '-'}</td>
                    <td>${o.color || '-'}</td>
                    <td>${o.size || '-'}</td>
                    <td>${o.target_qty || '-'}</td>
                    <td>${o.produced_qty || '-'}</td>
                    <td>${formatDateTime(o.completed_at)}</td>
                    <td>${deleteBtn}</td>
                </tr>`;
            });
        }
        tbody.innerHTML = rows;
    } catch (error) {
        tbody.innerHTML = '<tr><td colspan="13" style="color:#ef4444; text-align:center;">Error loading data</td></tr>';
    }
}

document.getElementById('btn-export-excel')?.addEventListener('click', () => {
    if (!completedOrders.length) {
        Swal.fire('Info', 'No data to export', 'info');
        return;
    }
    const seen = new Set();
    let csv = "Location,Machine,Work Order,Product,Brand,Customer,Description,Color,Size,Target,Produced,Completed\n";
    completedOrders.forEach(o => {
        const key = `${o.machine_id}_${o.completed_at}`;
        if (seen.has(key)) return;
        seen.add(key);
        const description = (o.description || '').replace(/"/g, '""');
        csv += `"${o.location_name}","${o.machine_name}","${o.work_order || ''}","${o.product}","${o.brand || ''}","${o.customer_name || ''}","${description}","${o.color}","${o.size}","${o.target_qty}","${o.produced_qty}","${o.completed_at}"\n`;
    });
    const blob = new Blob(["\uFEFF" + csv], {type: 'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `report_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
});

// ========== WASTE REPORT ==========
document.getElementById('btn-view-waste')?.addEventListener('click', async () => {
    await loadWasteReport();
    document.getElementById('waste-report-modal').classList.remove('hidden');
});

async function loadWasteReport() {
    const tbody = document.getElementById('waste-table-body');
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center;"><div class="loading"></div> Loading...</td></tr>';
    
    try {
        const response = await fetch(`${API_URL}?action=get_waste_report`);
        const data = await response.json();
        
        if (!data.success) {
            throw new Error(data.message || 'Failed to load waste report');
        }
        
        wasteData = data.data || [];
        
        let rows = '';
        let totalWaste = 0;
        let electricityWaste = 0;
        let todayWaste = 0;
        let weekWaste = 0;
        
        const today = new Date().toDateString();
        const weekAgo = new Date();
        weekAgo.setDate(weekAgo.getDate() - 7);
        
        if (wasteData.length === 0) {
            rows = '<tr><td colspan="9" style="text-align:center;">No waste records found</td></tr>';
        } else {
            wasteData.forEach(w => {
                const wasteQty = parseInt(w.waste_quantity) || 0;
                totalWaste += wasteQty;
                
                if (w.waste_reason === 'electricity') {
                    electricityWaste += wasteQty;
                }
                
                if (w.reported_at) {
                    const reportedDate = new Date(w.reported_at).toDateString();
                    if (reportedDate === today) {
                        todayWaste += wasteQty;
                    }
                    
                    if (new Date(w.reported_at) >= weekAgo) {
                        weekWaste += wasteQty;
                    }
                }
                
                const reasonIcon = {
                    'electricity': '⚡',
                    'mechanical': '🔧',
                    'material': '📦',
                    'operator': '👤',
                    'other': '❓'
                }[w.waste_reason] || '❓';
                
                const deleteBtn = currentUser?.role === 'manager' 
                    ? `<button class="delete-btn" onclick="deleteWasteRecord(${w.id})">Delete</button>`
                    : '-';
                
                rows += `<tr>
                    <td>${formatDateTime(w.reported_at)}</td>
                    <td>${w.machine_name || ''} (${w.machine_id || ''})</td>
                    <td>${w.product_name || '-'}</td>
                    <td>${w.color || '-'}</td>
                    <td>${w.size || '-'}</td>
                    <td style="color:#ef4444; font-weight:600;">${w.waste_quantity || 0}m</td>
                    <td>${reasonIcon} ${w.waste_reason || ''}</td>
                    <td>${w.notes || '-'}</td>
                    <td>${deleteBtn}</td>
                </tr>`;
            });
        }
        
        tbody.innerHTML = rows;
        
        document.getElementById('total-waste').textContent = totalWaste + ' m';
        document.getElementById('electricity-waste').textContent = electricityWaste + ' m';
        document.getElementById('today-waste').textContent = todayWaste + ' m';
        document.getElementById('week-waste').textContent = weekWaste + ' m';
        
        updateSummaryWidgets();
        
    } catch (error) {
        tbody.innerHTML = `<tr><td colspan="9" style="color:#ef4444; text-align:center;">Error: ${error.message}</td></tr>`;
        showNotification('Error loading waste report: ' + error.message, 'error');
    }
}

document.getElementById('btn-export-waste')?.addEventListener('click', () => {
    if (!wasteData.length) {
        Swal.fire('Info', 'No data to export', 'info');
        return;
    }
    
    let csv = "Date/Time,Machine,Product,Color,Size,Waste Quantity,Reason,Notes\n";
    wasteData.forEach(w => {
        const notes = (w.notes || '').replace(/"/g, '""');
        csv += `"${w.reported_at || ''}","${w.machine_name || ''} (${w.machine_id || ''})","${w.product_name || ''}","${w.color || ''}","${w.size || ''}","${w.waste_quantity || 0}","${w.waste_reason || ''}","${notes}"\n`;
    });
    
    const blob = new Blob(["\uFEFF" + csv], {type: 'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `waste_report_${new Date().toISOString().slice(0,10)}.csv`;
    a.click();
});

// ========== CLOSE BUTTONS ==========
document.getElementById('btn-clear-report')?.addEventListener('click', clearReport);
document.getElementById('btn-clear-downtime')?.addEventListener('click', clearDowntimeReport);
document.getElementById('btn-close-metrics')?.addEventListener('click', () => {
    document.getElementById('metrics-modal').classList.add('hidden');
});
document.getElementById('btn-close-downtime')?.addEventListener('click', () => {
    document.getElementById('downtime-modal').classList.add('hidden');
});
document.getElementById('waste-report-close')?.addEventListener('click', () => {
    document.getElementById('waste-report-modal').classList.add('hidden');
});

// ========== FILTERS ==========
let filterTimeout;
// Clear search filter on every page load (prevents browser autofill from persisting)
const filterSearchEl = document.getElementById('filter-search');
if (filterSearchEl) {
    filterSearchEl.value = '';
    filterSearchEl.dataset.userTyped = '0';
}

document.getElementById('filter-location')?.addEventListener('change', renderDashboard);
document.getElementById('filter-status')?.addEventListener('change', renderDashboard);
document.getElementById('filter-search')?.addEventListener('input', () => {
    const fsEl = document.getElementById('filter-search');
    if (fsEl) fsEl.dataset.userTyped = '1';
    clearTimeout(filterTimeout);
    filterTimeout = setTimeout(renderDashboard, 300);
});

// ========== KEYBOARD SHORTCUTS ==========
document.addEventListener('keydown', function(e) {
    if (e.ctrlKey && e.key === 'w') {
        e.preventDefault();
        document.getElementById('btn-view-waste')?.click();
    }
    if (e.ctrlKey && e.key === 'd') {
        e.preventDefault();
        document.getElementById('btn-view-downtime')?.click();
    }
    if (e.ctrlKey && e.key === 'r') {
        e.preventDefault();
        document.getElementById('btn-view-metrics')?.click();
    }
    if (e.key === 'Escape') {
        document.querySelectorAll('.modal:not(.hidden) .modal-cancel').forEach(btn => btn.click());
    }
});

// ========== EXPOSE FUNCTIONS TO WINDOW ==========
window.showProductionModal = showProductionModal;
window.showSalesOrderSearch = showSalesOrderSearch;
window.resetMachine = resetMachine;
window.showStopModal = showStopModal;
window.resumeMachine = resumeMachine;
window.completeMachine = completeMachine;
window.showWasteModal = showWasteModal;
window.showEditModal = showEditModal;
window.showSpeedModal = showSpeedModal;
window.adjustQuantity = adjustQuantity;
window.updateQuantityFromInput = updateQuantityFromInput;
window.adjustTarget = adjustTarget;
window.updateTargetFromInput = updateTargetFromInput;
window.deleteWasteRecord = deleteWasteRecord;
window.deleteDowntimeRecord = deleteDowntimeRecord;
window.deleteOrder = deleteOrder;
window.setSpeedPreset = setSpeedPreset;
window.adjustManualSpeed = adjustManualSpeed;
window.resetToDefault = resetToDefault;
window.selectSalesOrder = selectSalesOrder;
window.selectItemFromOrder = selectItemFromOrder;
window.selectItemFromOrderWithContext = selectItemFromOrderWithContext;
window.showRenameModal = showRenameModal;

console.log('%c✅ PRODUCTION DASHBOARD - CRON ONLY MODE', 'color: #60a5fa; font-size: 16px; font-weight: bold');
console.log('   ✅ CRON handles ALL production updates');
console.log('   ✅ Browser updates DISABLED');
console.log('   ✅ UI refreshes every 30 seconds');
console.log('   ✅ Version control active');
console.log('   ✅ Size optional - "No Size" option available');
console.log('   ✅ Admin can rename machines');
console.log('   ✅ No double updates');
</script>

<!-- ============================================================ -->
<!-- ========== MATERIAL MANAGEMENT SYSTEM - INLINE ============= -->
<!-- ============================================================ -->
<div id="mms-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:#0a0c15; z-index:10000; overflow-y:auto;">
<div style="max-width:1400px; margin:0 auto; padding:24px; font-family:'Inter',-apple-system,sans-serif;">

  <!-- MMS Header -->
  <div style="background:#1e2130; border-radius:24px; padding:20px 32px; margin-bottom:28px; border:1px solid rgba(139,92,246,.2); display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:16px;">
    <div>
      <h1 style="font-size:1.4rem; font-weight:700; background:linear-gradient(135deg,#f1f5f9,#cbd5e1); -webkit-background-clip:text; -webkit-text-fill-color:transparent;">📦 Material Management System</h1>
      <p style="font-size:.7rem; color:#9ca3af; margin-top:4px;">Multi-Location Inventory | Tassne Alladaen</p>
    </div>
    <button onclick="closeMaterialMgmt()" style="background:#ef4444; color:white; border:none; padding:10px 24px; border-radius:40px; font-weight:700; cursor:pointer; font-size:.9rem;">✕ Close</button>
  </div>

  <!-- Admin Global Location Filter -->
  <div id="mms-global-loc-bar" style="display:none; margin-bottom:20px; background:#1e2130; border-radius:16px; padding:14px 20px; border:1px solid rgba(139,92,246,.3); align-items:center; gap:12px; flex-wrap:wrap;">
    <span style="color:#a78bfa; font-size:.85rem; font-weight:700;">📍 Filter by Location:</span>
    <div id="mms-global-loc-btns" style="display:flex; gap:8px; flex-wrap:wrap;"></div>
  </div>

  <!-- Stats -->
  <div style="display:grid; grid-template-columns:repeat(4,1fr); gap:16px; margin-bottom:24px;" id="mms-stats-grid">
    <div style="background:#1e2130; border-radius:20px; padding:20px; border:1px solid rgba(139,92,246,.2); text-align:center;">
      <div style="font-size:2rem;">📦</div>
      <div style="font-size:.7rem; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; margin:8px 0;">Total Bags</div>
      <div style="font-size:1.8rem; font-weight:700; color:#f1f5f9;" id="mms-total-bags">-</div>
    </div>
    <div style="background:#1e2130; border-radius:20px; padding:20px; border:1px solid rgba(139,92,246,.2); text-align:center;">
      <div style="font-size:2rem;">⚖️</div>
      <div style="font-size:.7rem; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; margin:8px 0;">Total KG</div>
      <div style="font-size:1.8rem; font-weight:700; color:#f1f5f9;" id="mms-total-kg">-</div>
    </div>
    <div style="background:#1e2130; border-radius:20px; padding:20px; border:1px solid rgba(139,92,246,.2); text-align:center;">
      <div style="font-size:2rem;">🔄</div>
      <div style="font-size:.7rem; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; margin:8px 0;">Copper Rolls</div>
      <div style="font-size:1.8rem; font-weight:700; color:#f1f5f9;" id="mms-total-copper">-</div>
    </div>
    <div style="background:#1e2130; border-radius:20px; padding:20px; border:1px solid rgba(139,92,246,.2); text-align:center;">
      <div style="font-size:2rem;">⚠️</div>
      <div style="font-size:.7rem; color:#9ca3af; text-transform:uppercase; letter-spacing:1px; margin:8px 0;">Low Stock</div>
      <div style="font-size:1.8rem; font-weight:700; color:#f87171;" id="mms-low-stock">-</div>
    </div>
  </div>

  <!-- Tabs -->
  <div style="display:flex; gap:10px; margin-bottom:24px; flex-wrap:wrap;">
    <button class="mms-tab active" data-tab="mms-usage" onclick="mmsTab(this,'mms-usage')">📝 Report Usage</button>
    <button class="mms-tab" data-tab="mms-addstock" onclick="mmsTab(this,'mms-addstock')">➕ Add Stock</button>
    <button class="mms-tab" data-tab="mms-stock" onclick="mmsTab(this,'mms-stock')">📊 Stock Status</button>
    <button class="mms-tab" data-tab="mms-history" onclick="mmsTab(this,'mms-history')">📜 History</button>
    <button class="mms-tab" data-tab="mms-reports" onclick="mmsTab(this,'mms-reports')">📈 Analytics</button>
  </div>

  <!-- TAB: Report Usage -->
  <div id="mms-usage" class="mms-tab-content" style="background:#1e2130; border-radius:24px; padding:28px; border:1px solid rgba(139,92,246,.2);">
    <div id="mms-admin-usage-warn" style="display:none; background:rgba(239,68,68,.15); border:1px solid #ef4444; border-radius:12px; padding:16px; color:#f87171; margin-bottom:16px;">⚠️ Administrators cannot report usage. Login as operator.</div>
    <div id="mms-usage-form">
      <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
        <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">🕐 Shift</label>
          <select id="mms-shift" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; font-size:.9rem;">
            <option value="Morning">🌅 Morning (06:00AM - 6:00PM)</option>
            <option value="Evening">🌙 Evening (06:00PM - 06:00AM)</option>
          </select></div>
        <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📅 Date</label>
          <input type="date" id="mms-date" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; font-size:.9rem; color-scheme:dark;"></div>
      </div>
      <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📝 Notes</label>
        <textarea id="mms-notes" rows="2" placeholder="Remarks..." style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; font-size:.9rem; resize:vertical;"></textarea></div>
      <div style="overflow-x:auto;">
        <table style="width:100%; border-collapse:collapse;">
          <thead><tr>
            <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem; text-transform:uppercase;">Material</th>
            <th style="padding:12px; width:130px; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem; text-transform:uppercase;">Qty Used</th>
            <th style="padding:12px; width:100px; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem; text-transform:uppercase;">Total</th>
          </tr></thead>
          <tbody id="mms-usage-tbody"></tbody>
        </table>
      </div>
      <button onclick="mmsSubmitUsage()" style="width:100%; padding:14px; margin-top:20px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:700; font-size:1rem; cursor:pointer;">✓ Submit Consumption Report</button>
    </div>
  </div>

  <!-- TAB: Add Stock -->
  <div id="mms-addstock" class="mms-tab-content" style="display:none; background:#1e2130; border-radius:24px; padding:28px; border:1px solid rgba(139,92,246,.2);">
    <div id="mms-admin-stock-warn" style="display:none; background:rgba(239,68,68,.15); border:1px solid #ef4444; border-radius:12px; padding:16px; color:#f87171; margin-bottom:16px;">⚠️ Administrators cannot add stock. Login as operator.</div>
    <div id="mms-addstock-form">

      <!-- Sub-tabs -->
      <div style="display:flex; gap:8px; margin-bottom:24px; flex-wrap:wrap;">
        <button class="mms-sub-tab active" onclick="mmsSubTab(this,'mms-sub-addstock')" style="padding:9px 20px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:600; font-size:.82rem; cursor:pointer;">➕ Add Stock</button>
        <button class="mms-sub-tab" onclick="mmsSubTab(this,'mms-sub-newmat')" style="padding:9px 20px; background:#1e2130; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#cbd5e1; font-weight:600; font-size:.82rem; cursor:pointer;">🆕 New Material</button>
        <button class="mms-sub-tab" onclick="mmsSubTab(this,'mms-sub-editstock')" style="padding:9px 20px; background:#1e2130; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#cbd5e1; font-weight:600; font-size:.82rem; cursor:pointer;">✏️ Edit Stock</button>
        <button class="mms-sub-tab" onclick="mmsSubTab(this,'mms-sub-transfer')" style="padding:9px 20px; background:#1e2130; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#cbd5e1; font-weight:600; font-size:.82rem; cursor:pointer;">🔄 Transfer</button>

      </div>

      <!-- SUB: Add Stock -->
      <div id="mms-sub-addstock" class="mms-sub-content">
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📍 Location</label>
            <input id="mms-add-loc" type="text" readonly style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; font-weight:600;"></div>
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📅 Date</label>
            <input type="date" id="mms-add-date" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; font-size:.9rem; color-scheme:dark;"></div>
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📦 Material</label>
          <select id="mms-add-material" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"><option value="">Select Material</option></select></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📦 Quantity (Bags/Rolls)</label>
            <input type="number" id="mms-add-qty" min="1" placeholder="Enter quantity" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;">
            <small style="color:#9ca3af;">1 bag = 25 kg</small></div>
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">🧾 Invoice No</label>
            <input type="text" id="mms-add-invoice" placeholder="Invoice/reference" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></div>
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📝 Notes</label>
          <textarea id="mms-add-notes" rows="2" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; resize:vertical;"></textarea></div>
        <button onclick="mmsAddStock()" style="width:100%; padding:14px; background:#10b981; border:none; border-radius:40px; color:white; font-weight:700; font-size:1rem; cursor:pointer;">➕ Add Stock to Inventory</button>
      </div>

      <!-- SUB: New Material -->
      <div id="mms-sub-newmat" class="mms-sub-content" style="display:none;">
        <div style="background:rgba(139,92,246,.1); border:1px solid rgba(139,92,246,.3); border-radius:12px; padding:14px; margin-bottom:20px; color:#c4b5fd; font-size:.85rem;">
          ℹ️ Add New Material 
        </div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📦 Material Name</label>
            <input type="text" id="mms-new-mat-name" placeholder="e.g. HDPE 952" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></div>
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📐 Unit</label>
            <select id="mms-new-mat-unit" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;">
              <option value="bags">Bags (25kg each)</option>
              <option value="rolls">Rolls</option>
            </select></div>
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">⚠️ Min Stock Level</label>
          <input type="number" id="mms-new-mat-min" value="5" min="1" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></div>
        <button onclick="mmsAddNewMaterial()" style="width:100%; padding:14px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:700; font-size:1rem; cursor:pointer;">🆕 Add New Material</button>
      </div>

      <!-- SUB: Edit Stock -->
      <div id="mms-sub-editstock" class="mms-sub-content" style="display:none;">
        <div style="background:rgba(245,158,11,.1); border:1px solid rgba(245,158,11,.3); border-radius:12px; padding:14px; margin-bottom:20px; color:#fcd34d; font-size:.85rem;">
          ⚠️ Correct Your stock material
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📦 Material</label>
          <select id="mms-edit-op-material" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;" onchange="mmsShowCurrentStock()"><option value="">Select Material</option></select></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📊 Current Stock</label>
            <input id="mms-edit-op-current" readonly style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.1); border-radius:12px; color:#9ca3af;"></div>
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">✏️ New Quantity</label>
            <input type="number" id="mms-edit-op-new" min="0" placeholder="Enter new qty" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></div>
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📝 Reason (Required)</label>
          <textarea id="mms-edit-op-reason" rows="2" placeholder="Why are you changing the stock?" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; resize:vertical;"></textarea></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:12px;">
          <button onclick="mmsOperatorEditStock()" style="padding:14px; background:linear-gradient(135deg,#f59e0b,#ef4444); border:none; border-radius:40px; color:white; font-weight:700; font-size:.95rem; cursor:pointer;">✏️ Update Stock</button>
          <button onclick="mmsOperatorDeleteStock()" style="padding:14px; background:rgba(239,68,68,.2); border:1px solid #ef4444; border-radius:40px; color:#f87171; font-weight:700; font-size:.95rem; cursor:pointer;">🗑️ Delete (Set to 0)</button>
        </div>
      </div>

      <!-- SUB: Transfer -->
      <div id="mms-sub-transfer" class="mms-sub-content" style="display:none;">
        <div style="background:rgba(16,185,129,.1); border:1px solid rgba(16,185,129,.3); border-radius:12px; padding:14px; margin-bottom:20px; color:#6ee7b7; font-size:.85rem;">
          🔄 Transfer Material From One location to Another location
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📍 From Location</label>
          <input id="mms-tr-from" readonly style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.1); border-radius:12px; color:#a78bfa; font-weight:600;"></div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📦 Material</label>
          <select id="mms-tr-material" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;" onchange="mmsTrShowStock()"><option value="">Select Material</option></select></div>
        <div id="mms-tr-stock-info" style="display:none; background:#0f111f; border-radius:10px; padding:10px 14px; margin-bottom:16px; color:#4ade80; font-size:.85rem; font-weight:600;"></div>
        <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px; margin-bottom:16px;">
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">🎯 To Location</label>
            <select id="mms-tr-to" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></select></div>
          <div><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📊 Quantity</label>
            <input type="number" id="mms-tr-qty" min="1" placeholder="Qty to transfer" style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9;"></div>
        </div>
        <div style="margin-bottom:16px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">📝 Notes</label>
          <textarea id="mms-tr-notes" rows="2" placeholder="Reason for transfer..." style="width:100%; padding:12px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:12px; color:#f1f5f9; resize:vertical;"></textarea></div>
        <button onclick="mmsTransferStock()" style="width:100%; padding:14px; background:linear-gradient(135deg,#10b981,#3b82f6); border:none; border-radius:40px; color:white; font-weight:700; font-size:1rem; cursor:pointer;">🔄 Transfer Stock</button>
      </div>



    </div>
  </div>

  <!-- TAB: Stock Status -->
  <div id="mms-stock" class="mms-tab-content" style="display:none; background:#1e2130; border-radius:24px; padding:28px; border:1px solid rgba(139,92,246,.2);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
      <h3 style="color:#f1f5f9; font-size:1.1rem;">📊 Current Inventory</h3>
      <button onclick="mmsLoadStock()" style="background:linear-gradient(135deg,#8b5cf6,#ec489a); color:white; border:none; padding:8px 20px; border-radius:40px; font-weight:600; cursor:pointer;">⟳ Refresh</button>
    </div>
    <div id="mms-stock-loc-filter" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:20px;"></div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Location</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Material</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Stock Qty</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Weight</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Status</th>
          <th id="mms-stock-action-th" style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem; display:none;">Action</th>
        </tr></thead>
        <tbody id="mms-stock-tbody"><tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">Click Refresh to load...</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- TAB: History -->
  <div id="mms-history" class="mms-tab-content" style="display:none; background:#1e2130; border-radius:24px; padding:28px; border:1px solid rgba(139,92,246,.2);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px; flex-wrap:wrap; gap:12px;">
      <h3 style="color:#f1f5f9; font-size:1.1rem;">📜 Usage History</h3>
      <button onclick="mmsExport()" style="background:linear-gradient(135deg,#8b5cf6,#ec489a); color:white; border:none; padding:8px 20px; border-radius:40px; font-weight:600; cursor:pointer;">📥 Export CSV</button>
    </div>
    <div id="mms-hist-loc-filter" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;"></div>
    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap;">
      <input type="date" id="mms-hist-start" style="padding:10px 16px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#f1f5f9; color-scheme:dark;">
      <input type="date" id="mms-hist-end" style="padding:10px 16px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#f1f5f9; color-scheme:dark;">
      <button onclick="mmsLoadHistory()" style="padding:10px 20px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:600; cursor:pointer;">Apply</button>
    </div>
    <div style="overflow-x:auto;">
      <table style="width:100%; border-collapse:collapse;">
        <thead><tr>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Date</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Location</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Shift</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Material</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Qty</th>
          <th style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem;">Notes</th>
          <th id="mms-hist-del-th" style="padding:12px; text-align:left; background:rgba(0,0,0,.4); color:#a78bfa; font-size:.75rem; display:none;">Action</th>
        </tr></thead>
        <tbody id="mms-hist-tbody"><tr><td colspan="6" style="text-align:center; padding:30px; color:#9ca3af;">Select date range and click Apply</td></tr></tbody>
      </table>
    </div>
  </div>

  <!-- TAB: Analytics -->
  <div id="mms-reports" class="mms-tab-content" style="display:none; background:#1e2130; border-radius:24px; padding:28px; border:1px solid rgba(139,92,246,.2);">
    <div id="mms-rep-loc-filter" style="display:flex; gap:10px; flex-wrap:wrap; margin-bottom:16px;"></div>
    <div style="display:flex; gap:10px; margin-bottom:20px; flex-wrap:wrap; align-items:center;">
      <select id="mms-rep-period" style="padding:10px 16px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#f1f5f9;">
        <option value="today">Today</option>
        <option value="week">This Week</option>
        <option value="month">This Month</option>
        <option value="all">All Time</option>
      </select>
      <button onclick="mmsLoadReport()" style="padding:10px 20px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:600; cursor:pointer;">Generate</button>
    </div>
    <div style="height:280px;"><canvas id="mms-chart"></canvas></div>
    <div style="display:grid; grid-template-columns:repeat(3,1fr); gap:16px; margin-top:24px;">
      <div style="background:#0f111f; border-radius:16px; padding:16px; text-align:center; border:1px solid rgba(139,92,246,.2);">
        <div style="font-size:1.5rem; font-weight:700; color:#f1f5f9;" id="mms-rep-qty">-</div>
        <div style="font-size:.7rem; color:#9ca3af; margin-top:4px;">Total Units</div>
      </div>
      <div style="background:#0f111f; border-radius:16px; padding:16px; text-align:center; border:1px solid rgba(139,92,246,.2);">
        <div style="font-size:1.5rem; font-weight:700; color:#f1f5f9;" id="mms-rep-kg">-</div>
        <div style="font-size:.7rem; color:#9ca3af; margin-top:4px;">Total KG</div>
      </div>
      <div style="background:#0f111f; border-radius:16px; padding:16px; text-align:center; border:1px solid rgba(139,92,246,.2);">
        <div style="font-size:1rem; font-weight:700; color:#f1f5f9;" id="mms-rep-top">-</div>
        <div style="font-size:.7rem; color:#9ca3af; margin-top:4px;">Most Used</div>
      </div>
    </div>
  </div>

  <!-- Edit Stock Modal -->
  <div id="mms-edit-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,.85); backdrop-filter:blur(8px); justify-content:center; align-items:center; z-index:3000;">
    <div style="background:#1e2130; border-radius:24px; padding:28px; width:90%; max-width:450px; border:1px solid rgba(139,92,246,.4);">
      <h3 style="color:#f1f5f9; margin-bottom:20px;">✏️ Correct Stock</h3>
      <input type="hidden" id="mms-edit-mid"><input type="hidden" id="mms-edit-loc">
      <div style="margin-bottom:12px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">Material</label>
        <input id="mms-edit-name" readonly style="width:100%; padding:10px; background:#0f111f; border:1px solid rgba(139,92,246,.2); border-radius:10px; color:#f1f5f9;"></div>
      <div style="margin-bottom:12px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">Current Qty</label>
        <input id="mms-edit-cur" readonly style="width:100%; padding:10px; background:#0f111f; border:1px solid rgba(139,92,246,.2); border-radius:10px; color:#f1f5f9;"></div>
      <div style="margin-bottom:12px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">New Qty</label>
        <input type="number" id="mms-edit-new" min="0" style="width:100%; padding:10px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:10px; color:#f1f5f9;"></div>
      <div style="margin-bottom:20px;"><label style="display:block; font-size:.8rem; color:#cbd5e1; margin-bottom:6px;">Reason</label>
        <textarea id="mms-edit-reason" rows="2" style="width:100%; padding:10px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:10px; color:#f1f5f9; resize:vertical;"></textarea></div>
      <div style="display:flex; gap:12px;">
        <button onclick="mmsSubmitEdit()" style="flex:1; padding:12px; background:linear-gradient(135deg,#8b5cf6,#ec489a); border:none; border-radius:40px; color:white; font-weight:700; cursor:pointer;">Save</button>
        <button onclick="document.getElementById('mms-edit-modal').style.display='none'" style="flex:1; padding:12px; background:#374151; border:none; border-radius:40px; color:white; font-weight:600; cursor:pointer;">Cancel</button>
      </div>
    </div>
  </div>

</div><!-- end app-container -->
</div><!-- end mms-overlay -->

<style>
.mms-tab { padding:10px 24px; background:#1e2130; border:1px solid rgba(139,92,246,.3); border-radius:50px; color:#cbd5e1; font-weight:600; font-size:.85rem; cursor:pointer; transition:all .3s; }
.mms-tab.active { background:linear-gradient(135deg,#8b5cf6,#ec489a); border-color:transparent; color:white; }
.mms-tab:hover:not(.active) { background:rgba(139,92,246,.2); color:white; }
.mms-loc-btn { padding:7px 20px; background:#0f111f; border:1px solid rgba(139,92,246,.3); border-radius:40px; color:#cbd5e1; font-size:.8rem; font-weight:500; cursor:pointer; transition:all .3s; }
.mms-loc-btn.active { background:linear-gradient(135deg,#8b5cf6,#ec489a); border-color:transparent; color:white; }
@media(max-width:768px){
  #mms-stats-grid { grid-template-columns:1fr 1fr !important; }
  .mms-tab { padding:8px 16px; font-size:.75rem; }
}
</style>

<script>
// ============================================================
// ========== MATERIAL MANAGEMENT SYSTEM JS ===================
// ============================================================
const MMS_API = 'api.php';
let mmsChart      = null;
let mmsMaterials  = [];
let mmsIsAdmin    = false;
let mmsUserLoc    = 'all';
let mmsStockLoc   = 'all';
let mmsHistLoc    = 'all';
let mmsRepLoc     = 'all';
let mmsHistData   = [];

async function mmsApi(params) {
    const qs = new URLSearchParams(params).toString();
    const r  = await fetch(`${MMS_API}?${qs}`);
    return r.json();
}
async function mmsPost(params, body) {
    const qs = new URLSearchParams(params).toString();
    const r  = await fetch(`${MMS_API}?${qs}`, { method:'POST', headers:{'Content-Type':'application/json'}, body:JSON.stringify(body) });
    return r.json();
}

function mmsToday() { return new Date().toISOString().split('T')[0]; }

async function openMaterialMgmt() {
    document.getElementById('mms-overlay').style.display = 'block';
    document.body.style.overflow = 'hidden';

    // Detect role from existing session badge text
    const badge = document.querySelector('.badge-role');
    const badgeText = badge ? badge.textContent.toLowerCase() : '';
    mmsIsAdmin  = badgeText.includes('manager') || badgeText.includes('admin');
    mmsUserLoc  = 'all';

    // Get location from badge e.g. "Operator (Modan)"
    const locMatch = badgeText.match(/\(([^)]+)\)/);
    if (locMatch) {
        const raw = locMatch[1].trim();
        const lmap = { 'modan':'Modan', 'baldeya':'Baldeya', 'al-khraj':'Al-Khraj', 'alkhraj':'Al-Khraj' };
        mmsUserLoc = lmap[raw.toLowerCase()] || raw;
    }

    mmsStockLoc = mmsIsAdmin ? 'all' : mmsUserLoc;
    mmsHistLoc  = mmsIsAdmin ? 'all' : mmsUserLoc;
    mmsRepLoc   = mmsIsAdmin ? 'all' : mmsUserLoc;

    // Set dates
    document.getElementById('mms-date').value     = mmsToday();
    document.getElementById('mms-add-date').value = mmsToday();
    document.getElementById('mms-add-loc').value  = mmsUserLoc === 'all' ? 'Admin (View Only)' : mmsUserLoc;

    // Admin warnings
    document.getElementById('mms-admin-usage-warn').style.display = mmsIsAdmin ? 'block' : 'none';
    document.getElementById('mms-usage-form').style.display       = mmsIsAdmin ? 'none'  : 'block';
    document.getElementById('mms-admin-stock-warn').style.display  = mmsIsAdmin ? 'block' : 'none';
    document.getElementById('mms-addstock-form').style.display     = mmsIsAdmin ? 'none'  : 'block';
    document.getElementById('mms-stock-action-th').style.display   = mmsIsAdmin ? '' : 'none';

    // Build location filters
    mmsBuildLocFilters();

    // Global admin location filter
    const globalBar = document.getElementById('mms-global-loc-bar');
    if (mmsIsAdmin) {
        globalBar.style.display = 'flex';
        const btnsDiv = document.getElementById('mms-global-loc-btns');
        btnsDiv.innerHTML = '';
        const locNames = { all:'🌍 All Locations', Modan:'📍 Modan', Baldeya:'📍 Baldeya', 'Al-Khraj':'📍 Al-Khraj' };
        ['all','Modan','Baldeya','Al-Khraj'].forEach(loc => {
            const btn = document.createElement('button');
            btn.className = 'mms-loc-btn' + (loc === 'all' ? ' active' : '');
            btn.textContent = locNames[loc];
            btn.dataset.loc = loc;
            btn.onclick = () => {
                btnsDiv.querySelectorAll('.mms-loc-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                mmsStockLoc = loc;
                mmsHistLoc  = loc;
                mmsRepLoc   = loc;
                mmsLoadStats();
                // Also refresh whichever tab is currently active
                const activeTab = document.querySelector('.mms-tab.active')?.dataset?.tab;
                if (activeTab === 'mms-stock')   mmsLoadStock();
                if (activeTab === 'mms-history') mmsLoadHistory();
                if (activeTab === 'mms-reports') mmsLoadReport();
            };
            btnsDiv.appendChild(btn);
        });
    } else {
        globalBar.style.display = 'none';
    }

    await mmsLoadMaterials();
    mmsLoadStats();
    mmsLoadUsageTable();
    mmsFillMaterialSelect();
    mmsPopulateEditSelect();
    mmsPopulateTransferSelects();
}

function closeMaterialMgmt() {
    document.getElementById('mms-overlay').style.display = 'none';
    document.body.style.overflow = '';
}

function mmsTab(btn, tabId) {
    document.querySelectorAll('.mms-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    document.querySelectorAll('.mms-tab-content').forEach(t => t.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    if (tabId === 'mms-stock')   mmsLoadStock();
    if (tabId === 'mms-history') mmsLoadHistory();
    if (tabId === 'mms-reports') mmsLoadReport();
}

function mmsBuildLocFilters() {
    const locs  = mmsIsAdmin ? ['all','Modan','Baldeya','Al-Khraj'] : [mmsUserLoc];
    const names = { all:'🌍 All', Modan:'📍 Modan', Baldeya:'📍 Baldeya', 'Al-Khraj':'📍 Al-Khraj' };
    [
        ['mms-stock-loc-filter', () => mmsLoadStock(),   () => mmsStockLoc, v => mmsStockLoc = v],
        ['mms-hist-loc-filter',  () => mmsLoadHistory(), () => mmsHistLoc,  v => mmsHistLoc  = v],
        ['mms-rep-loc-filter',   () => mmsLoadReport(),  () => mmsRepLoc,   v => mmsRepLoc   = v],
    ].forEach(([id, fn, get, set]) => {
        const div = document.getElementById(id);
        if (!div) return;
        div.innerHTML = '';
        locs.forEach(loc => {
            const btn = document.createElement('button');
            btn.className = 'mms-loc-btn' + (loc === get() ? ' active' : '');
            btn.textContent = names[loc] || loc;
            btn.onclick = () => {
                div.querySelectorAll('.mms-loc-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                set(loc);
                fn();
            };
            div.appendChild(btn);
        });
    });
}

async function mmsLoadMaterials() {
    const res = await mmsApi({ action: 'mms_get_materials' });
    if (res.success) mmsMaterials = res.data;
}

function mmsLoadUsageTable() {
    const tbody = document.getElementById('mms-usage-tbody');
    if (!tbody) return;
    tbody.innerHTML = '';
    mmsMaterials.forEach(m => {
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;">
                <strong>${m.material_name}</strong><br>
                <small style="color:#9ca3af;">${m.unit==='bags'?'25kg/bag':'Rolls'}</small>
            </td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);">
                <input type="number" class="mms-qty-input" data-id="${m.id}" data-unit="${m.unit}"
                    min="0" value="0"
                    style="width:100px;padding:8px;background:#0f111f;border:1px solid rgba(139,92,246,.3);border-radius:10px;color:#f1f5f9;text-align:center;">
            </td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#c084fc;">
                <span id="mms-tot-${m.id}">0</span> ${m.unit==='bags'?'kg':'rolls'}
            </td>`;
        tbody.appendChild(tr);
        tr.querySelector('.mms-qty-input').addEventListener('input', function() {
            const v = parseFloat(this.value) || 0;
            document.getElementById('mms-tot-' + this.dataset.id).textContent = m.unit === 'bags' ? v * 25 : v;
        });
    });
}

function mmsFillMaterialSelect() {
    const sel = document.getElementById('mms-add-material');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select Material</option>';
    mmsMaterials.forEach(m => {
        sel.innerHTML += `<option value="${m.id}">${m.material_name} (${m.unit==='bags'?'25kg/bag':'Roll'})</option>`;
    });
}

async function mmsLoadStats() {
    const loc = mmsIsAdmin ? mmsStockLoc : mmsUserLoc;
    const res = await mmsApi({ action: 'mms_get_stats', location: loc });
    if (!res.success) return;
    document.getElementById('mms-total-bags').textContent   = res.data.total_bags;
    document.getElementById('mms-total-kg').textContent     = res.data.total_kg;
    document.getElementById('mms-total-copper').textContent = res.data.total_copper;
    document.getElementById('mms-low-stock').textContent    = res.data.low_stock;
}

async function mmsLoadStock() {
    const tbody = document.getElementById('mms-stock-tbody');
    tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">Loading...</td></tr>';
    const res = await mmsApi({ action: 'mms_get_stock', location: mmsStockLoc });
    if (!res.success || !res.data.length) {
        tbody.innerHTML = '<tr><td colspan="6" style="text-align:center;padding:30px;color:#9ca3af;">No data found</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    res.data.forEach(r => {
        const qty = parseFloat(r.quantity) || 0;
        const min = parseFloat(r.min_stock_level) || 5;
        let status, color, bgRow = '';
        if (qty <= 0)       { status = '🔴 Critical'; color = '#f87171'; bgRow = 'background:rgba(239,68,68,.1);'; }
        else if (qty < min) { status = '🟡 Low';      color = '#fbbf24'; }
        else                { status = '🟢 Good';     color = '#4ade80'; }
        const kg  = r.unit === 'bags' ? (qty * 25) + ' kg' : '-';
        const editBtn = mmsIsAdmin ? `<button onclick="mmsOpenEdit(${r.material_id},'${r.location_name}','${r.material_name}',${qty})"
            style="background:#f59e0b;color:white;border:none;padding:5px 14px;border-radius:20px;font-size:.75rem;cursor:pointer;font-weight:600;">✏️ Edit</button>` : '';
        tbody.innerHTML += `<tr style="${bgRow}">
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);color:#c084fc;font-weight:600;">${r.location_name}</td>
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;">${r.material_name}</td>
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;font-weight:600;">${qty} ${r.unit}</td>
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;">${kg}</td>
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);color:${color};font-weight:600;">${status}</td>
            <td style="padding:12px;border-bottom:1px solid rgba(139,92,246,.1);">${editBtn}</td>
        </tr>`;
    });
}

async function mmsLoadHistory() {
    const tbody = document.getElementById('mms-hist-tbody');
    tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#9ca3af;">Loading...</td></tr>';
    const res = await mmsApi({ action:'mms_get_history', location:mmsHistLoc, start:document.getElementById('mms-hist-start').value||'', end:document.getElementById('mms-hist-end').value||'' });
    mmsHistData = res.data || [];
    // Show/hide delete column for admin
    const delTh = document.getElementById('mms-hist-del-th');
    if (delTh) delTh.style.display = mmsIsAdmin ? '' : 'none';
    if (!res.success || !mmsHistData.length) {
        tbody.innerHTML = '<tr><td colspan="7" style="text-align:center;padding:30px;color:#9ca3af;">No records found</td></tr>';
        return;
    }
    tbody.innerHTML = '';
    mmsHistData.forEach(r => {
        const delBtn = mmsIsAdmin
            ? `<button onclick="mmsDeleteHistory(${r.id})" style="background:rgba(239,68,68,.2);border:1px solid #ef4444;color:#f87171;padding:4px 12px;border-radius:20px;font-size:.75rem;cursor:pointer;font-weight:600;">🗑️ Del</button>`
            : '';
        tbody.innerHTML += `<tr>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;">${r.report_date}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#c084fc;">${r.location_name}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);">${r.shift==='Morning'?'🌅 Morning':'🌙 Evening'}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;font-weight:600;">${r.material_name}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#f1f5f9;">${r.bags_used} ${r.unit}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);color:#9ca3af;">${r.notes||'-'}</td>
            <td style="padding:10px;border-bottom:1px solid rgba(139,92,246,.1);">${delBtn}</td>
        </tr>`;
    });
}

async function mmsLoadReport() {
    const res = await mmsApi({ action:'mms_get_report', location:mmsRepLoc, period:document.getElementById('mms-rep-period').value });
    if (!res.success) return;
    const d = res.data;
    document.getElementById('mms-rep-qty').textContent = d.total_qty;
    document.getElementById('mms-rep-kg').textContent  = d.total_kg;
    document.getElementById('mms-rep-top').textContent = d.top_material || '-';
    if (mmsChart) mmsChart.destroy();
    const ctx = document.getElementById('mms-chart').getContext('2d');
    mmsChart = new Chart(ctx, {
        type: 'bar',
        data: { labels: d.chart_labels, datasets: [{ label:'Usage', data:d.chart_data, backgroundColor:'rgba(139,92,246,.7)', borderColor:'#8b5cf6', borderWidth:1, borderRadius:8 }] },
        options: { responsive:true, maintainAspectRatio:false,
            plugins:{ legend:{ labels:{ color:'#cbd5e1' } } },
            scales:{ y:{ ticks:{color:'#cbd5e1'}, grid:{color:'rgba(139,92,246,.1)'} }, x:{ ticks:{color:'#cbd5e1'}, grid:{display:false} } }
        }
    });
}

async function mmsSubmitUsage() {
    const shift = document.getElementById('mms-shift').value;
    const date  = document.getElementById('mms-date').value;
    const notes = document.getElementById('mms-notes').value;
    if (!date) { Swal.fire('Error','Please select a date','warning'); return; }
    const items = [];
    document.querySelectorAll('.mms-qty-input').forEach(inp => {
        const qty = parseFloat(inp.value) || 0;
        if (qty > 0) items.push({ material_id: inp.dataset.id, qty });
    });
    if (!items.length) { Swal.fire('Error','Please enter at least one quantity','warning'); return; }
    const cf = await Swal.fire({
        title:'Confirm Usage Report',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📍 Location:</b> ${mmsUserLoc}</p><p><b>🕐 Shift:</b> ${shift}</p><p><b>📅 Date:</b> ${date}</p><hr style="border-color:#8b5cf6;margin:10px 0">
        ${items.map(i=>{const m=mmsMaterials.find(x=>x.id==i.material_id);return m?`<p>📦 ${m.material_name}: ${i.qty} ${m.unit}</p>`:''}).join('')}</div>`,
        icon:'question', showCancelButton:true, confirmButtonText:'✅ Confirm',
        background:'#1e2130', color:'#f1f5f9',
        allowOutsideClick: false, allowEscapeKey: false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({ action:'mms_submit_usage' }, { shift, date, notes, location:mmsUserLoc, items });
    if (res.success) {
        document.querySelectorAll('.mms-qty-input').forEach(i => { i.value=0; });
        document.querySelectorAll('[id^="mms-tot-"]').forEach(s => s.textContent='0');
        document.getElementById('mms-notes').value = '';
        mmsLoadStats();
        Swal.fire({
            title:'✅ Submitted!',
            text:'Usage report saved successfully.',
            icon:'success',
            background:'#1e2130',
            color:'#f1f5f9',
            confirmButtonText:'OK',
            allowOutsideClick: false,
            allowEscapeKey: false,
            target: document.getElementById('mms-overlay'),
            timer: 2000,
            timerProgressBar: true
        });
    } else {
        Swal.fire({
            title:'Error', text: res.message || 'Failed', icon:'error',
            allowOutsideClick: false, target: document.getElementById('mms-overlay')
        });
    }
}

async function mmsAddStock() {
    const mid     = document.getElementById('mms-add-material').value;
    const qty     = parseFloat(document.getElementById('mms-add-qty').value);
    const date    = document.getElementById('mms-add-date').value;
    const invoice = document.getElementById('mms-add-invoice').value;
    const notes   = document.getElementById('mms-add-notes').value;
    if (!mid || !qty || qty <= 0 || !date) { Swal.fire({ title:'Error', text:'Please fill all fields', icon:'warning', allowOutsideClick:false, target:document.getElementById('mms-overlay') }); return; }
    const mat = mmsMaterials.find(m => m.id == mid);
    const cf = await Swal.fire({
        title:'Confirm Stock Addition',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📍 Location:</b> ${mmsUserLoc}</p><p><b>📦 Material:</b> ${mat?.material_name}</p><p><b>📊 Quantity:</b> ${qty} ${mat?.unit}</p><p><b>📅 Date:</b> ${date}</p>${invoice?`<p><b>🧾 Invoice:</b> ${invoice}</p>`:''}</div>`,
        icon:'question', showCancelButton:true, confirmButtonText:'✅ Add Stock',
        background:'#1e2130', color:'#f1f5f9',
        allowOutsideClick: false, allowEscapeKey: false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({ action:'mms_add_stock' }, { material_id:mid, qty, date, invoice, notes, location:mmsUserLoc });
    if (res.success) {
        document.getElementById('mms-add-material').value = '';
        document.getElementById('mms-add-qty').value = '';
        document.getElementById('mms-add-invoice').value = '';
        document.getElementById('mms-add-notes').value = '';
        mmsLoadStats();
        Swal.fire({
            title:'✅ Stock Added!', text:'Inventory updated successfully.',
            icon:'success', background:'#1e2130', color:'#f1f5f9',
            confirmButtonText:'OK', allowOutsideClick:false, allowEscapeKey:false,
            target: document.getElementById('mms-overlay'),
            timer: 2000, timerProgressBar: true
        });
    } else {
        Swal.fire({ title:'Error', text: res.message || 'Failed', icon:'error', allowOutsideClick:false, target:document.getElementById('mms-overlay') });
    }
}

function mmsOpenEdit(mid, loc, name, curQty) {
    document.getElementById('mms-edit-mid').value    = mid;
    document.getElementById('mms-edit-loc').value    = loc;
    document.getElementById('mms-edit-name').value   = name;
    document.getElementById('mms-edit-cur').value    = curQty;
    document.getElementById('mms-edit-new').value    = curQty;
    document.getElementById('mms-edit-reason').value = '';
    document.getElementById('mms-edit-modal').style.display = 'flex';
}

async function mmsSubmitEdit() {
    const mid    = document.getElementById('mms-edit-mid').value;
    const loc    = document.getElementById('mms-edit-loc').value;
    const newQty = parseFloat(document.getElementById('mms-edit-new').value);
    const reason = document.getElementById('mms-edit-reason').value;
    if (isNaN(newQty) || newQty < 0) { Swal.fire('Error','Invalid quantity','warning'); return; }
    const res = await mmsPost({ action:'mms_edit_stock' }, { material_id:mid, location:loc, new_qty:newQty, reason });
    if (res.success) {
        document.getElementById('mms-edit-modal').style.display = 'none';
        mmsLoadStock();
        mmsLoadStats();
        Swal.fire('Success','Stock corrected!','success');
    } else {
        Swal.fire('Error', res.message || 'Failed', 'error');
    }
}

function mmsExport() {
    if (!mmsHistData.length) { Swal.fire('Info','No data to export','info'); return; }
    let csv = "Date,Location,Shift,Material,Quantity,Unit,Notes\n";
    mmsHistData.forEach(r => {
        csv += `"${r.report_date}","${r.location_name}","${r.shift}","${r.material_name}","${r.bags_used}","${r.unit}","${(r.notes||'').replace(/"/g,'""')}"\n`;
    });
    const blob = new Blob(["\uFEFF"+csv], {type:'text/csv'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = `material_usage_${mmsToday()}.csv`;
    a.click();
}

window.openMaterialMgmt  = openMaterialMgmt;
window.closeMaterialMgmt = closeMaterialMgmt;
window.mmsDeleteHistory  = mmsDeleteHistory;
// ========== SUB-TAB SWITCHER ==========
function mmsSubTab(btn, tabId) {
    document.querySelectorAll('.mms-sub-tab').forEach(b => {
        b.style.background = '#1e2130';
        b.style.border = '1px solid rgba(139,92,246,.3)';
        b.style.color = '#cbd5e1';
    });
    btn.style.background = 'linear-gradient(135deg,#8b5cf6,#ec489a)';
    btn.style.border = 'none';
    btn.style.color = 'white';
    document.querySelectorAll('.mms-sub-content').forEach(t => t.style.display = 'none');
    document.getElementById(tabId).style.display = 'block';
    if (tabId === 'mms-sub-editstock') mmsPopulateEditSelect();
    if (tabId === 'mms-sub-transfer')  mmsPopulateTransferSelects();
}

// ========== ADD NEW MATERIAL ==========
async function mmsAddNewMaterial() {
    const name = document.getElementById('mms-new-mat-name').value.trim();
    const unit = document.getElementById('mms-new-mat-unit').value;
    const min  = document.getElementById('mms-new-mat-min').value || 5;
    if (!name) { Swal.fire({title:'Error',text:'Material name required',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    const cf = await Swal.fire({
        title:'Add New Material?',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📦 Name:</b> ${name}</p><p><b>📐 Unit:</b> ${unit}</p><p><b>⚠️ Min Stock:</b> ${min}</p><p style="color:#9ca3af;margin-top:10px;font-size:.85rem;">Will be added to all 3 locations with 0 stock.</p></div>`,
        icon:'question', showCancelButton:true, confirmButtonText:'✅ Add',
        background:'#1e2130', color:'#f1f5f9', allowOutsideClick:false, allowEscapeKey:false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({action:'mms_add_material'},{name,unit,min_stock:min});
    if (res.success) {
        document.getElementById('mms-new-mat-name').value = '';
        await mmsLoadMaterials();
        mmsFillMaterialSelect();
        mmsPopulateEditSelect();
        mmsPopulateTransferSelects();
        Swal.fire({title:'✅ Added!',text:`"${name}" added to all locations.`,icon:'success',background:'#1e2130',color:'#f1f5f9',timer:2000,timerProgressBar:true,allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    } else {
        Swal.fire({title:'Error',text:res.message||'Failed',icon:'error',allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    }
}

// ========== POPULATE EDIT SELECT ==========
function mmsPopulateEditSelect() {
    const sel = document.getElementById('mms-edit-op-material');
    if (!sel) return;
    sel.innerHTML = '<option value="">Select Material</option>';
    mmsMaterials.forEach(m => { sel.innerHTML += `<option value="${m.id}">${m.material_name} (${m.unit})</option>`; });
    document.getElementById('mms-edit-op-current').value = '';
    document.getElementById('mms-edit-op-new').value = '';
}

async function mmsShowCurrentStock() {
    const mid = document.getElementById('mms-edit-op-material').value;
    if (!mid) { document.getElementById('mms-edit-op-current').value = ''; return; }
    const res = await mmsApi({action:'mms_get_stock', location: mmsUserLoc});
    if (res.success) {
        const row = res.data.find(r => r.material_id == mid);
        document.getElementById('mms-edit-op-current').value = row ? `${row.quantity} ${row.unit}` : '0';
        document.getElementById('mms-edit-op-new').value = row ? row.quantity : 0;
    }
}

// ========== OPERATOR EDIT STOCK ==========
async function mmsOperatorEditStock() {
    const mid    = document.getElementById('mms-edit-op-material').value;
    const newQty = parseFloat(document.getElementById('mms-edit-op-new').value);
    const reason = document.getElementById('mms-edit-op-reason').value.trim();
    const mat    = mmsMaterials.find(m => m.id == mid);
    if (!mid || isNaN(newQty) || newQty < 0) { Swal.fire({title:'Error',text:'Select material and enter valid qty',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    if (!reason) { Swal.fire({title:'Error',text:'Reason is required',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    const cf = await Swal.fire({
        title:'Confirm Stock Edit',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📦 Material:</b> ${mat?.material_name}</p><p><b>📍 Location:</b> ${mmsUserLoc}</p><p><b>✏️ New Qty:</b> ${newQty} ${mat?.unit}</p><p><b>📝 Reason:</b> ${reason}</p></div>`,
        icon:'warning', showCancelButton:true, confirmButtonText:'✅ Update',
        background:'#1e2130', color:'#f1f5f9', allowOutsideClick:false, allowEscapeKey:false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({action:'mms_operator_edit_stock'},{material_id:mid,new_qty:newQty,reason});
    if (res.success) {
        document.getElementById('mms-edit-op-reason').value = '';
        mmsLoadStats();
        mmsShowCurrentStock();
        Swal.fire({title:'✅ Updated!',text:'Stock corrected.',icon:'success',background:'#1e2130',color:'#f1f5f9',timer:2000,timerProgressBar:true,allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    } else {
        Swal.fire({title:'Error',text:res.message||'Failed',icon:'error',allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    }
}

// ========== OPERATOR DELETE STOCK ==========
async function mmsOperatorDeleteStock() {
    const mid    = document.getElementById('mms-edit-op-material').value;
    const reason = document.getElementById('mms-edit-op-reason').value.trim();
    const mat    = mmsMaterials.find(m => m.id == mid);
    if (!mid) { Swal.fire({title:'Error',text:'Select a material first',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    if (!reason) { Swal.fire({title:'Error',text:'Reason is required',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    const cf = await Swal.fire({
        title:'⚠️ Delete Stock?',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📦 Material:</b> ${mat?.material_name}</p><p><b>📍 Location:</b> ${mmsUserLoc}</p><p style="color:#f87171;margin-top:8px;">Stock will be set to <b>0</b>.</p></div>`,
        icon:'warning', showCancelButton:true, confirmButtonText:'🗑️ Delete',
        confirmButtonColor:'#ef4444',
        background:'#1e2130', color:'#f1f5f9', allowOutsideClick:false, allowEscapeKey:false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({action:'mms_delete_stock'},{material_id:mid,reason});
    if (res.success) {
        document.getElementById('mms-edit-op-reason').value = '';
        mmsLoadStats();
        mmsShowCurrentStock();
        Swal.fire({title:'✅ Done!',text:'Stock set to 0.',icon:'success',background:'#1e2130',color:'#f1f5f9',timer:2000,timerProgressBar:true,allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    } else {
        Swal.fire({title:'Error',text:res.message||'Failed',icon:'error',allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    }
}

// ========== TRANSFER STOCK ==========
function mmsPopulateTransferSelects() {
    const matSel = document.getElementById('mms-tr-material');
    if (!matSel) return;
    matSel.innerHTML = '<option value="">Select Material</option>';
    mmsMaterials.forEach(m => { matSel.innerHTML += `<option value="${m.id}">${m.material_name} (${m.unit})</option>`; });
    document.getElementById('mms-tr-from').value = mmsUserLoc;
    const toSel = document.getElementById('mms-tr-to');
    toSel.innerHTML = '';
    ['Modan','Baldeya','Al-Khraj'].filter(l => l !== mmsUserLoc).forEach(l => {
        toSel.innerHTML += `<option value="${l}">${l}</option>`;
    });
    document.getElementById('mms-tr-stock-info').style.display = 'none';
}

async function mmsTrShowStock() {
    const mid = document.getElementById('mms-tr-material').value;
    const info = document.getElementById('mms-tr-stock-info');
    if (!mid) { info.style.display='none'; return; }
    const res = await mmsApi({action:'mms_get_stock', location: mmsUserLoc});
    if (res.success) {
        const row = res.data.find(r => r.material_id == mid);
        const qty = row ? row.quantity : 0;
        const unit = row ? row.unit : '';
        info.style.display = 'block';
        info.textContent = `📊 Available at ${mmsUserLoc}: ${qty} ${unit}`;
        info.style.color = qty > 0 ? '#4ade80' : '#f87171';
    }
}

async function mmsTransferStock() {
    const mid   = document.getElementById('mms-tr-material').value;
    const toLoc = document.getElementById('mms-tr-to').value;
    const qty   = parseFloat(document.getElementById('mms-tr-qty').value);
    const notes = document.getElementById('mms-tr-notes').value.trim();
    const mat   = mmsMaterials.find(m => m.id == mid);
    if (!mid || !toLoc || !qty || qty <= 0) { Swal.fire({title:'Error',text:'Fill all fields',icon:'warning',allowOutsideClick:false,target:document.getElementById('mms-overlay')}); return; }
    const cf = await Swal.fire({
        title:'Confirm Transfer',
        html:`<div style="text-align:left;color:#f1f5f9;"><p><b>📦 Material:</b> ${mat?.material_name}</p><p><b>📍 From:</b> ${mmsUserLoc}</p><p><b>🎯 To:</b> ${toLoc}</p><p><b>📊 Qty:</b> ${qty} ${mat?.unit}</p></div>`,
        icon:'question', showCancelButton:true, confirmButtonText:'🔄 Transfer',
        background:'#1e2130', color:'#f1f5f9', allowOutsideClick:false, allowEscapeKey:false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({action:'mms_transfer_stock'},{material_id:mid,qty,to_location:toLoc,notes});
    if (res.success) {
        document.getElementById('mms-tr-qty').value = '';
        document.getElementById('mms-tr-notes').value = '';
        mmsLoadStats();
        mmsTrShowStock();
        Swal.fire({title:'✅ Transferred!',text:`${qty} ${mat?.unit} moved to ${toLoc}.`,icon:'success',background:'#1e2130',color:'#f1f5f9',timer:2500,timerProgressBar:true,allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    } else {
        Swal.fire({title:'Error',text:res.message||'Failed',icon:'error',allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    }
}


window.mmsSubTab             = mmsSubTab;
window.mmsAddNewMaterial     = mmsAddNewMaterial;
window.mmsShowCurrentStock   = mmsShowCurrentStock;
window.mmsOperatorEditStock  = mmsOperatorEditStock;
window.mmsOperatorDeleteStock= mmsOperatorDeleteStock;
window.mmsTrShowStock        = mmsTrShowStock;
window.mmsTransferStock      = mmsTransferStock;
window.mmsTab            = mmsTab;
window.mmsLoadStock      = mmsLoadStock;
async function mmsDeleteHistory(id) {
    const cf = await Swal.fire({
        title:'Delete Record?',
        text:'This usage record will be permanently deleted.',
        icon:'warning', showCancelButton:true, confirmButtonText:'🗑️ Delete',
        confirmButtonColor:'#ef4444',
        background:'#1e2130', color:'#f1f5f9',
        allowOutsideClick:false, allowEscapeKey:false,
        target: document.getElementById('mms-overlay')
    });
    if (!cf.isConfirmed) return;
    const res = await mmsPost({action:'mms_delete_history'},{id});
    if (res.success) {
        mmsLoadHistory();
        mmsLoadStats();
        Swal.fire({title:'✅ Deleted!',text:'Record removed.',icon:'success',background:'#1e2130',color:'#f1f5f9',timer:1500,timerProgressBar:true,allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    } else {
        Swal.fire({title:'Error',text:res.message||'Failed',icon:'error',allowOutsideClick:false,target:document.getElementById('mms-overlay')});
    }
}

window.mmsDeleteHistory = mmsDeleteHistory;
window.mmsLoadHistory    = mmsLoadHistory;
window.mmsLoadReport     = mmsLoadReport;
window.mmsSubmitUsage    = mmsSubmitUsage;
window.mmsAddStock       = mmsAddStock;
window.mmsOpenEdit       = mmsOpenEdit;
window.mmsSubmitEdit     = mmsSubmitEdit;
window.mmsExport         = mmsExport;
</script>

</body> 
</html>