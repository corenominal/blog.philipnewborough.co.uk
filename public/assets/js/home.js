(function () {
  'use strict';

  const sentinel = document.getElementById('post-list-sentinel');
  if (!sentinel) return;

  const postList = document.getElementById('post-list');
  let offset = parseInt(sentinel.dataset.offset, 10) || 0;
  const endpointUrl = sentinel.dataset.url;
  let loading = false;

  const observer = new IntersectionObserver(
    (entries) => {
      if (entries[0].isIntersecting && !loading) {
        loadMore();
      }
    },
    { rootMargin: '200px' }
  );

  observer.observe(sentinel);

  function loadMore() {
    loading = true;

    fetch(`${endpointUrl}?offset=${offset}`)
      .then((res) => {
        if (!res.ok) throw new Error('Network error');
        return res.json();
      })
      .then((json) => {
        if (json.status === 'success' && json.data.length > 0) {
          appendPosts(json.data);
          offset += json.data.length;
        }
        if (!json.hasMore) {
          observer.unobserve(sentinel);
          sentinel.remove();
        }
      })
      .catch(() => {
        sentinel.remove();
      })
      .finally(() => {
        loading = false;
      });
  }

  function appendPosts(posts) {
    posts.forEach((post) => {
      const li = document.createElement('li');
      li.className =
        'post-list__item h-entry d-flex align-items-baseline gap-3 py-3 border-bottom';

      const titleHtml = post.title_html
        ? decodeEntities(post.title_html)
        : escHtml(post.title);
      const excerptHtml = post.excerpt
        ? `<p class="post-list__excerpt text-muted small mb-0 mt-1 p-summary">${escHtml(post.excerpt)}</p>`
        : '';

      let tagsHtml = '';
      if (post.tags_list && post.tags_list.length > 0) {
        const tagLinks = post.tags_list
          .map(
            (tag) =>
              `<a class="badge text-decoration-none p-category" style="background-color:#44475a;color:#f8f8f2;" href="${endpointBase()}tags/${encodeURIComponent(tag.slug)}">${escHtml(tag.tag)}</a>`
          )
          .join('');
        tagsHtml = `<div class="post-list__tags d-flex flex-wrap gap-1 mt-2">${tagLinks}</div>`;
      }

      li.innerHTML = `
        <time class="post-list__date text-muted small text-nowrap flex-shrink-0 dt-published" datetime="${escAttr(post.published_at_iso)}">
          ${escHtml(post.published_at_formatted)}
        </time>
        <div class="post-list__body">
          <a class="post-list__title text-white fw-semibold text-decoration-none link-hover p-name u-url" href="${endpointBase()}posts/${encodeURIComponent(post.slug)}">
            ${titleHtml}
          </a>
          ${excerptHtml}
          ${tagsHtml}
        </div>`;

      postList.appendChild(li);
    });
  }

  /** Derive the site base URL from the sentinel's data-url (everything up to "home/posts"). */
  function endpointBase() {
    return endpointUrl.replace(/home\/posts$/, '');
  }

  function escHtml(str) {
    if (str == null) return '';
    return String(str)
      .replace(/&/g, '&amp;')
      .replace(/</g, '&lt;')
      .replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;')
      .replace(/'/g, '&#039;');
  }

  function escAttr(str) {
    if (str == null) return '';
    return String(str).replace(/"/g, '&quot;');
  }

  /** Decode HTML entities from title_html (admin-controlled DB content). */
  function decodeEntities(str) {
    const el = document.createElement('textarea');
    el.innerHTML = str;
    return el.value;
  }
}());
