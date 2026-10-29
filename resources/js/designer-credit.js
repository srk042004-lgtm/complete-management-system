const addDesignerCredit = () => {
    if (!document.body || document.querySelector('[data-designer-credit]')) return;

    const credit = document.createElement('aside');
    credit.className = 'designer-credit';
    credit.dataset.designerCredit = '';
    credit.setAttribute('aria-label', 'ArtFlow design credit and contact');
    credit.innerHTML = '<span>Created at Artflow Studio · Designed by Shahrukh Khan</span><a href="tel:+923109540857">0310 9540857</a>';
    document.body.append(credit);
};

if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', addDesignerCredit, { once: true });
} else {
    addDesignerCredit();
}
