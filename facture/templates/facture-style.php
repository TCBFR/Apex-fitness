<style>
        @page { margin: 0; }
        * { box-sizing: border-box; }
        body { margin: 0; color: #edf3f0; background: #050a09; font-family: "DejaVu Sans", sans-serif; font-size: 10px; }
        .sheet { position: relative; padding: 0 44px 30px; }
        .hero { position: relative; height: 128px; margin: 0 -44px; padding: 0 44px; overflow: hidden; background: #07100d; border-bottom: 1px solid #26332c; }
        .hero-photo { position: absolute; top: -7px; right: 42px; width: 370px; height: 144px; opacity: .42; }
        .hero-content { position: relative; z-index: 2; width: 100%; height: 128px; border-collapse: collapse; }
        .hero-content td { vertical-align: middle; }
        .brand-cell { width: 68%; }
        .brand-lockup { border-collapse: collapse; }
        .brand-mark { width: 54px; height: 54px; padding-right: 8px; color: #b7ef30; font-size: 51px; font-weight: bold; line-height: 1; }
        .brand-logo { width: 48px; height: 52px; }
        .brand-name { color: #f6f8f6; font-size: 23px; font-weight: bold; letter-spacing: 3px; }
        .tagline { padding-top: 5px; color: #c0ccc5; font-size: 8px; font-weight: bold; letter-spacing: 3px; }
        .invoice-head { color: #e9efeb; font-size: 10px; font-weight: bold; letter-spacing: 2px; text-align: right; }
        .invoice-number { margin-top: 7px; color: #c6f432; font-size: 12px; letter-spacing: .5px; }
        .lime-rule { height: 3px; background: #c6f432; }
        .parties { width: 100%; margin: 25px 0 26px; border-collapse: collapse; }
        .parties td { width: 50%; padding: 0 22px 0 0; vertical-align: top; }
        .parties td + td { padding-left: 24px; border-left: 1px solid #34423b; }
        .section-title { margin: 0 0 9px; color: #c6f432; font-size: 9px; font-weight: bold; letter-spacing: 1.5px; }
        .party-name { margin-bottom: 4px; color: #f5f8f6; font-size: 11px; font-weight: bold; }
        .party-details { color: #c4d0ca; font-size: 9px; line-height: 1.65; }
        .contact-icon { color: #c6f432; font-size: 11px; font-weight: bold; }
        .contact-row { margin-top: 6px; }
        .separator { height: 1px; background: #34423b; }
        .invoice-overview { width: 100%; margin: 23px 0 25px; border-collapse: collapse; }
        .invoice-overview td { width: 53%; vertical-align: top; }
        .invoice-overview td + td { width: 47%; padding-left: 24px; border-left: 1px solid #34423b; }
        h1 { margin: 0 0 5px; color: #f4f7f5; font-size: 28px; letter-spacing: -.5px; }
        .subtitle { color: #aebdb5; font-size: 12px; font-weight: bold; }
        .meta { width: 100%; border-collapse: collapse; }
        .meta td { width: 50%; padding: 5px 0; color: #aab8b0; font-size: 9px; }
        .meta td + td { padding-left: 8px; border: 0; color: #f1f5f2; font-weight: bold; text-align: right; }
        .items { width: 100%; border: 1px solid #263630; border-radius: 7px; border-collapse: separate; border-spacing: 0; }
        .items th { padding: 12px 10px; border-bottom: 1px solid #34423b; color: #c6f432; font-size: 8px; font-weight: bold; letter-spacing: 1.3px; text-align: left; }
        .items th.num, .items td.num { text-align: right; }
        .items td { padding: 17px 10px; border-bottom: 1px solid #1e2b25; vertical-align: middle; }
        .item-name { color: #f4f7f5; font-size: 10px; font-weight: bold; }
        .item-detail { margin-top: 5px; color: #aebdb5; font-size: 8px; line-height: 1.5; }
        .item-symbol { display: inline-block; width: 31px; height: 31px; margin-right: 9px; border: 1px solid #3f5a21; border-radius: 7px; background: #14200e; color: #c6f432; font-size: 15px; font-weight: bold; line-height: 31px; text-align: center; vertical-align: middle; }
        .thanks-totals { width: 100%; margin-top: 15px; border-collapse: collapse; }
        .thanks-totals > tbody > tr > td { width: 55%; padding: 0 8px; vertical-align: middle; }
        .thanks-totals > tbody > tr > td + td { width: 45%; padding-left: 20px; }
        .thanks { padding: 12px; border: 1px solid #263b30; border-radius: 8px; background: #09130f; }
        .thanks-title { margin-bottom: 5px; color: #c6f432; font-size: 8px; font-weight: bold; }
        .thanks-copy { color: #aebdb5; font-size: 8px; line-height: 1.5; }
        .totals { width: 100%; border-collapse: collapse; }
        .totals td { padding: 5px 0; color: #dce4df; font-size: 9px; }
        .totals td + td { color: #f4f7f5; font-weight: bold; text-align: right; }
        .grand-total td { padding-top: 10px; border-top: 1px solid #728b39; color: #c6f432; font-size: 10px; font-weight: bold; }
        .grand-total td + td { font-size: 15px; }
        .motto { width: 100%; margin-top: 18px; padding: 12px 0; border-top: 1px solid #34423b; border-bottom: 1px solid #34423b; border-collapse: collapse; }
        .motto td { width: 33.33%; padding: 3px 12px; color: #c3d0c8; font-size: 8px; line-height: 1.5; vertical-align: middle; }
        .motto td + td { border-left: 1px solid #34423b; }
        .motto-mark { padding-right: 7px; color: #c6f432; font-size: 17px; font-weight: bold; }
        .footer { width: 100%; margin-top: 14px; border-collapse: collapse; }
        .footer td { width: 65%; color: #9dada4; font-size: 8px; line-height: 1.7; }
        .footer td + td { width: 35%; color: #c6f432; font-size: 10px; font-weight: bold; letter-spacing: 1.5px; text-align: right; }
    </style>
