const predefinedUsers = [
  { id: "sofia", name: "Sofia Adler", role: "Editor" },
  { id: "leo", name: "Leo Marin", role: "Reporter" },
  { id: "maya", name: "Maya Toma", role: "Community Curator" },
];

const articles = [
  {
    id: crypto.randomUUID(),
    authorId: "leo",
    kind: "news",
    title: "Neighborhood library expands evening hours",
    summary:
      "The city approved a six-month pilot to keep the main branch open later on weekdays.",
    body:
      "Residents requested more evening access for students and workers, and the pilot program begins next Monday. Organizers plan to collect attendance data and public feedback through the summer.",
    createdAt: new Date().toISOString(),
  },
  {
    id: crypto.randomUUID(),
    authorId: "maya",
    kind: "event",
    title: "Open forum on public transport this Saturday",
    summary:
      "Local organizers will host a public forum for residents to discuss routes, frequency, and accessibility.",
    body:
      "The event starts at 14:00 in the Riverside Hall and includes a moderated discussion, a feedback station, and a short presentation from mobility advocates.",
    createdAt: new Date(Date.now() - 1000 * 60 * 60 * 8).toISOString(),
  },
  {
    id: crypto.randomUUID(),
    authorId: "sofia",
    kind: "opinion",
    title: "Why curated contributor access can strengthen trust",
    summary:
      "A clearly defined contributor roster can improve editorial accountability and reduce identity abuse.",
    body:
      "Closed account creation does not guarantee quality on its own, but it creates a stronger foundation for responsibility. Readers know who is publishing, and editors can support contributors with clearer expectations.",
    createdAt: new Date(Date.now() - 1000 * 60 * 60 * 28).toISOString(),
  },
];

const authorSelect = document.querySelector("#author");
const articleForm = document.querySelector("#article-form");
const articlesRoot = document.querySelector("#articles");
const filterRoot = document.querySelector("#filters");

let activeFilter = "all";

function formatTimestamp(isoDate) {
  return new Intl.DateTimeFormat("en", {
    dateStyle: "medium",
    timeStyle: "short",
  }).format(new Date(isoDate));
}

function getAuthor(authorId) {
  return predefinedUsers.find((user) => user.id === authorId);
}

function renderAuthorOptions() {
  authorSelect.innerHTML = predefinedUsers
    .map(
      (user) =>
        `<option value="${user.id}">${user.name} · ${user.role}</option>`,
    )
    .join("");
}

function renderArticles() {
  const visibleArticles = articles
    .filter((article) => activeFilter === "all" || article.kind === activeFilter)
    .sort((left, right) => new Date(right.createdAt) - new Date(left.createdAt));

  if (visibleArticles.length === 0) {
    articlesRoot.innerHTML =
      '<div class="empty-state">No articles match this filter yet.</div>';
    return;
  }

  articlesRoot.innerHTML = visibleArticles
    .map((article) => {
      const author = getAuthor(article.authorId);

      return `
        <article class="article-card">
          <div class="article-card__meta">
            <span class="article-card__kind">${article.kind}</span>
            <span>${author.name}</span>
            <span>${formatTimestamp(article.createdAt)}</span>
          </div>
          <h3>${article.title}</h3>
          <p>${article.summary}</p>
          <p class="article-card__body">${article.body}</p>
        </article>
      `;
    })
    .join("");
}

function setActiveFilter(nextFilter) {
  activeFilter = nextFilter;

  [...filterRoot.querySelectorAll(".filter-chip")].forEach((button) => {
    button.classList.toggle(
      "is-active",
      button.dataset.filter === activeFilter,
    );
  });

  renderArticles();
}

articleForm.addEventListener("submit", (event) => {
  event.preventDefault();

  const formData = new FormData(articleForm);

  articles.push({
    id: crypto.randomUUID(),
    authorId: formData.get("author"),
    kind: formData.get("kind"),
    title: formData.get("title").trim(),
    summary: formData.get("summary").trim(),
    body: formData.get("body").trim(),
    createdAt: new Date().toISOString(),
  });

  articleForm.reset();
  renderAuthorOptions();
  setActiveFilter("all");
});

filterRoot.addEventListener("click", (event) => {
  const target = event.target.closest(".filter-chip");

  if (!target) {
    return;
  }

  setActiveFilter(target.dataset.filter);
});

renderAuthorOptions();
renderArticles();
