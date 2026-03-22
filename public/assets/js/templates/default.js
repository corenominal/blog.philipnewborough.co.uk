document.addEventListener('DOMContentLoaded', () => {
    // Shimmer effect for post body images
    document.querySelectorAll('.post__body img').forEach((img) => {
        if (img.complete && img.naturalWidth > 0) {
            return;
        }

        const parent = img.parentNode;
        parent.classList.add('img-shimmer');

        const onLoad = () => parent.classList.remove('img-shimmer');
        img.addEventListener('load', onLoad, { once: true });
        img.addEventListener('error', onLoad, { once: true });
    });

    // Image modal
    const modal = document.createElement('div');
    modal.id = 'img-modal';
    modal.setAttribute('role', 'dialog');
    modal.setAttribute('aria-modal', 'true');
    modal.setAttribute('aria-label', 'Image viewer');
    modal.innerHTML = `
        <div class="img-modal__backdrop"></div>
        <div class="img-modal__content">
            <button class="img-modal__close" aria-label="Close">&times;</button>
            <div class="img-modal__img-wrap">
                <img class="img-modal__img" src="" alt="">
            </div>
        </div>
    `;
    document.body.appendChild(modal);

    const modalImg = modal.querySelector('.img-modal__img');
    const imgWrap  = modal.querySelector('.img-modal__img-wrap');

    const openModal = (src, alt) => {
        imgWrap.classList.add('img-modal__shimmer');
        modalImg.style.opacity = '0';
        modalImg.src = '';
        modal.classList.add('img-modal--open');
        document.body.classList.add('img-modal-open');
        modalImg.alt = alt || '';
        modalImg.src = src;

        const onLoad = () => {
            imgWrap.classList.remove('img-modal__shimmer');
            modalImg.style.opacity = '1';
        };
        modalImg.addEventListener('load', onLoad, { once: true });
        modalImg.addEventListener('error', onLoad, { once: true });
    };

    const closeModal = () => {
        modal.classList.remove('img-modal--open');
        document.body.classList.remove('img-modal-open');
        modal.addEventListener('transitionend', () => {
            modalImg.src = '';
        }, { once: true });
    };

    document.querySelectorAll('.post__body img').forEach((img) => {
        img.addEventListener('click', () => openModal(img.src, img.alt));
    });

    modal.querySelector('.img-modal__close').addEventListener('click', closeModal);
    modal.querySelector('.img-modal__backdrop').addEventListener('click', closeModal);

    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closeModal();
    });

    // Copy button for code blocks
    document.querySelectorAll('.post__body pre > code').forEach((codeEl) => {
        const pre = codeEl.parentElement;

        const wrapper = document.createElement('div');
        wrapper.className = 'code-block';
        pre.parentNode.insertBefore(wrapper, pre);
        wrapper.appendChild(pre);

        const btn = document.createElement('button');
        btn.className = 'code-block__copy-btn';
        btn.setAttribute('aria-label', 'Copy code');
        btn.innerHTML = '<i class="bi bi-clipboard" aria-hidden="true"></i>';

        btn.addEventListener('click', () => {
            navigator.clipboard.writeText(codeEl.textContent).then(() => {
                btn.innerHTML = '<i class="bi bi-clipboard-check" aria-hidden="true"></i>';
                btn.setAttribute('aria-label', 'Copied!');
                btn.classList.add('code-block__copy-btn--copied');
                setTimeout(() => {
                    btn.innerHTML = '<i class="bi bi-clipboard" aria-hidden="true"></i>';
                    btn.setAttribute('aria-label', 'Copy code');
                    btn.classList.remove('code-block__copy-btn--copied');
                }, 2000);
            });
        });

        wrapper.appendChild(btn);
    });
});

