{{-- Styles du bulletin de paie au format PDF. --}}
        body { font-family: DejaVu Sans, sans-serif; font-size: 10pt; color: #222; margin: 30px; }
        h1 { font-size: 14pt; text-align: center; margin-bottom: 5px; }
        .periode { text-align: center; font-size: 11pt; color: #666; margin-bottom: 20px; }
        .entete { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .entete .bloc { width: 48%; border: 1px solid #ccc; padding: 10px; }
        .entete h3 { font-size: 10pt; margin: 0 0 6px 0; color: #1B4965; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin: 15px 0; }
        th, td { padding: 5px 8px; border-bottom: 1px solid #eee; }
        th { background: #f8f9fa; text-align: left; font-size: 9pt; }
        .text-end { text-align: right; }
        .totaux { background: #e8f4f8; font-weight: bold; }
        .net { background: #d4edda; font-weight: bold; font-size: 11pt; }
        .footer { margin-top: 40px; font-size: 8pt; color: #999; text-align: center; }
    
        .saut-page { page-break-after: always; }
        .apercu { border: 2px dashed #D4A94D; padding: 4px 8px; text-align: center; color: #B8860B; font-weight: bold; margin-bottom: 10px; }
