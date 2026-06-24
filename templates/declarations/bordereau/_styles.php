<?php
return <<<'CSS'
* { box-sizing: border-box; margin: 0; padding: 0; }
body { font-family: Arial, Helvetica, sans-serif; font-size: 10pt; color: #111; background: #e8e8e8; padding: 12mm; }
.form-box { background: #fff; border: 2px solid #222; padding: 10mm; max-width: 210mm; margin: 0 auto; }
.form-header { display: flex; justify-content: space-between; align-items: flex-start; border-bottom: 2px solid #222; padding-bottom: 8px; margin-bottom: 12px; }
.org-block { display: flex; gap: 10px; align-items: center; }
.org-logo { width: 48px; height: 48px; border: 2px solid #0d9488; border-radius: 4px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 9pt; color: #0d9488; text-align: center; line-height: 1.1; }
.org-logo.g50 { border-color: #1e40af; color: #1e40af; }
.org-logo.btp { border-color: #b45309; color: #b45309; font-size: 7pt; }
.small { font-size: 8pt; color: #444; }
.form-ref { text-align: right; }
.ref-box { border: 1px solid #222; padding: 6px 10px; font-size: 9pt; margin-bottom: 4px; display: inline-block; }
.info-table { width: 100%; border-collapse: collapse; margin-bottom: 12px; }
.info-table td { border: 1px solid #666; padding: 6px 8px; vertical-align: top; }
.lbl { font-size: 7pt; text-transform: uppercase; color: #555; letter-spacing: 0.03em; }
.mono { font-family: 'Courier New', monospace; }
.big { font-size: 13pt; }
.lines-table { width: 100%; border-collapse: collapse; margin: 10px 0; }
.lines-table th, .lines-table td { border: 1px solid #222; padding: 5px 6px; }
.lines-table th { background: #f3f4f6; font-size: 8pt; text-transform: uppercase; }
.num { text-align: right; font-family: 'Courier New', monospace; }
.center { text-align: center; }
.right { text-align: right; }
.total-row td { background: #f9fafb; border-top: 2px solid #222; }
.sign-block { margin-top: 20px; font-size: 9pt; }
.sign-line { margin-top: 28px; border-top: 1px solid #999; padding-top: 6px; width: 55%; }
.footer-meta { margin-top: 16px; font-size: 7pt; color: #666; display: flex; justify-content: space-between; border-top: 1px dashed #ccc; padding-top: 6px; }
.btn-print { margin-bottom: 12px; padding: 8px 16px; cursor: pointer; background: #0d9488; color: #fff; border: none; border-radius: 4px; }
@media print { body { background: #fff; padding: 0; } .no-print { display: none !important; } .form-box { border: none; } }
CSS;
