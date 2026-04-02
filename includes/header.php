<?php
if (!isset($pageTitle)) {
    $pageTitle = 'PTMS';
}

if (!function_exists('e')) {
    function e($value) {
        return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle); ?></title>
    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: Arial, Helvetica, sans-serif;
            background:#f4f7fb;
            color:#1f2937;
        }

        .layout{
            display:flex;
            min-height:100vh;
        }

        .sidebar{
            width:250px;
            background:#123e82;
            color:#fff;
            position:fixed;
            top:0;
            left:0;
            bottom:0;
            overflow-y:auto;
            padding-top:20px;
            z-index:1000;
        }

        .sidebar .brand{
            text-align:center;
            font-size:22px;
            font-weight:bold;
            margin-bottom:25px;
            padding:0 15px;
        }

        .sidebar a{
            display:block;
            color:#fff;
            text-decoration:none;
            padding:14px 22px;
            font-size:15px;
            transition:0.3s;
        }

        .sidebar a:hover,
        .sidebar a.active{
            background:rgba(255,255,255,0.12);
            border-left:4px solid #fff;
            padding-left:18px;
        }

        .main{
            margin-left:250px;
            width:calc(100% - 250px);
            padding:24px;
        }

        .topbar{
            background:#fff;
            padding:22px;
            border-radius:16px;
            box-shadow:0 4px 18px rgba(0,0,0,0.06);
            margin-bottom:20px;
        }

        .page-title h1{
            font-size:28px;
            color:#123e82;
            margin-bottom:8px;
        }

        .page-title p{
            color:#6b7280;
            font-size:15px;
        }

        .hero-panel{
            background:linear-gradient(135deg, #123e82, #1f5fbf);
            color:#fff;
            border-radius:18px;
            padding:28px;
            display:flex;
            justify-content:space-between;
            align-items:center;
            gap:20px;
            margin-bottom:20px;
            flex-wrap:wrap;
        }

        .hero-panel h1,
        .hero-panel h2{
            font-size:26px;
            margin-bottom:10px;
        }

        .hero-panel p{
            font-size:15px;
            max-width:760px;
            line-height:1.6;
        }

        .highlight{
            color:#ffe082;
        }

        .actions{
            display:flex;
            gap:12px;
            flex-wrap:wrap;
        }

        .btn{
            display:inline-block;
            padding:12px 18px;
            border-radius:10px;
            text-decoration:none;
            font-weight:bold;
            font-size:14px;
            transition:0.3s;
            cursor:pointer;
            border:none;
        }

        .btn-primary{
            background:#fff;
            color:#123e82;
        }

        .btn-primary:hover{
            background:#eaf1ff;
        }

        .btn-outline{
            border:2px solid #fff;
            color:#fff;
            background:transparent;
        }

        .btn-outline:hover{
            background:rgba(255,255,255,0.1);
        }

        .btn-blue{
            background:#123e82;
            color:#fff;
        }

        .btn-blue:hover{
            background:#0f356f;
        }

        .btn-light{
            background:#eef2ff;
            color:#123e82;
        }

        .btn-light:hover{
            background:#dfe7ff;
        }

        .grid-4{
            display:grid;
            grid-template-columns:repeat(4, 1fr);
            gap:16px;
        }

        .grid-3{
            display:grid;
            grid-template-columns:repeat(3, 1fr);
            gap:16px;
        }

        .grid-2{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:16px;
        }

        .stat-card,
        .table-card,
        .content-card{
            background:#fff;
            border-radius:16px;
            padding:22px;
            box-shadow:0 4px 18px rgba(0,0,0,0.06);
        }

        .stat-card h3{
            font-size:15px;
            color:#6b7280;
            margin-bottom:12px;
        }

        .stat-card strong{
            display:block;
            font-size:32px;
            color:#123e82;
            margin-bottom:6px;
        }

        .stat-card span{
            font-size:13px;
            color:#9ca3af;
        }

        .table-card h2,
        .content-card h2{
            color:#123e82;
            font-size:21px;
            margin-bottom:15px;
        }

        .table-wrap{
            overflow-x:auto;
        }

        table{
            width:100%;
            border-collapse:collapse;
        }

        table th,
        table td{
            padding:12px 10px;
            border-bottom:1px solid #e5e7eb;
            text-align:left;
            font-size:14px;
            vertical-align:top;
        }

        table th{
            background:#f9fafb;
            color:#374151;
        }

        .empty{
            text-align:center;
            color:#6b7280;
            padding:18px;
        }

        .badge{
            display:inline-block;
            padding:6px 10px;
            border-radius:999px;
            font-size:12px;
            font-weight:bold;
            background:#e8f0ff;
            color:#123e82;
        }

        .section-space{
            margin-top:18px;
        }

        .small{
            font-size:12px;
            color:#6b7280;
            margin-top:4px;
        }

        .alert{
            padding:14px 16px;
            border-radius:12px;
            margin-bottom:16px;
            font-size:14px;
            font-weight:600;
        }

        .alert-success{
            background:#ecfdf3;
            color:#166534;
            border:1px solid #bbf7d0;
        }

        .alert-error{
            background:#fef2f2;
            color:#991b1b;
            border:1px solid #fecaca;
        }

        .info-list{
            display:grid;
            gap:14px;
        }

        .info-item{
            padding:14px;
            border-radius:12px;
            background:#f8fafc;
            line-height:1.6;
            border:1px solid #e5e7eb;
        }

        .form-grid{
            display:grid;
            grid-template-columns:1fr 1fr;
            gap:14px;
        }

        .form-group{
            display:flex;
            flex-direction:column;
            gap:8px;
        }

        .form-group.full{
            grid-column:1 / -1;
        }

        .form-group label{
            font-size:14px;
            font-weight:600;
            color:#374151;
        }

        .form-group input,
        .form-group select,
        .form-group textarea{
            width:100%;
            border:1px solid #d1d5db;
            border-radius:10px;
            padding:11px 12px;
            font-size:14px;
            outline:none;
            background:#fff;
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus{
            border-color:#123e82;
            box-shadow:0 0 0 3px rgba(18,62,130,0.12);
        }

        .form-group textarea{
            resize:vertical;
            min-height:110px;
        }

        .modal{
            position:fixed;
            inset:0;
            background:rgba(15,23,42,0.55);
            display:none;
            align-items:center;
            justify-content:center;
            padding:20px;
            z-index:2000;
        }

        .modal.show{
            display:flex;
        }

        .modal-box{
            width:min(680px, 100%);
            background:#fff;
            border-radius:18px;
            box-shadow:0 25px 60px rgba(0,0,0,0.20);
            overflow:hidden;
            animation:modalPop .18s ease-out;
        }

        @keyframes modalPop{
            from{
                opacity:0;
                transform:translateY(10px) scale(.98);
            }
            to{
                opacity:1;
                transform:translateY(0) scale(1);
            }
        }

        .modal-header,
        .modal-footer{
            padding:18px 20px;
            display:flex;
            align-items:center;
            justify-content:space-between;
            gap:12px;
        }

        .modal-header{
            border-bottom:1px solid #e5e7eb;
        }

        .modal-footer{
            border-top:1px solid #e5e7eb;
            justify-content:flex-end;
            flex-wrap:wrap;
        }

        .modal-header h3{
            font-size:20px;
            color:#123e82;
        }

        .modal-body{
            padding:20px;
        }

        .modal-close{
            border:none;
            background:transparent;
            font-size:28px;
            line-height:1;
            cursor:pointer;
            color:#6b7280;
        }

        .modal-note{
            background:#eef4ff;
            color:#123e82;
            padding:12px 14px;
            border-radius:10px;
            margin-bottom:14px;
            font-size:14px;
        }

        @media (max-width: 1100px){
            .grid-4{
                grid-template-columns:repeat(2, 1fr);
            }

            .grid-3,
            .grid-2{
                grid-template-columns:1fr;
            }

            .form-grid{
                grid-template-columns:1fr;
            }
        }

        @media (max-width: 768px){
            .layout{
                flex-direction:column;
            }

            .sidebar{
                position:relative;
                width:100%;
                height:auto;
            }

            .main{
                margin-left:0;
                width:100%;
            }

            .grid-4{
                grid-template-columns:1fr;
            }
        }
    </style>
</head>
<body>
<div class="layout">