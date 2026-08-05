/* global path_to_root */

(function () {
    "use strict";

    // mdBook only supplies headings for the active page. Keep this outline synchronized with public h2/h3 headings.
    const headingsByChapter = {
        "index.html": [
            { id: "status", title: "Status" },
            { id: "start-here", title: "Start Here" },
        ],
        "getting-started.html": [
            { id: "installation", title: "Installation" },
            { id: "verify-the-command", title: "Verify the Command" },
            { id: "development", title: "Development" },
        ],
    };

    function createHeadingList(pageUrl, headings) {
        const list = document.createElement("ol");
        list.classList.add("section");

        for (const heading of headings) {
            const item = document.createElement("li");
            item.classList.add("header-item");

            const wrapper = document.createElement("span");
            wrapper.classList.add("chapter-link-wrapper");

            const link = document.createElement("a");
            link.href = `${pageUrl}#${heading.id}`;
            link.textContent = heading.title;

            wrapper.append(link);
            item.append(wrapper);
            list.append(item);
        }

        return list;
    }

    document.addEventListener("DOMContentLoaded", function () {
        const chapterLinks = document.querySelectorAll("#mdbook-sidebar .chapter-item > .chapter-link-wrapper > a");

        for (const [chapterPath, headings] of Object.entries(headingsByChapter)) {
            const pageUrl = new URL(path_to_root + chapterPath, document.location.href);
            const chapterLink = Array.from(chapterLinks).find(function (link) {
                return new URL(link.href, document.location.href).href === pageUrl.href;
            });

            if (!chapterLink || chapterLink.classList.contains("active")) {
                continue;
            }

            const container = document.createElement("div");
            container.classList.add("akashi-page-outline");
            container.append(createHeadingList(pageUrl.href, headings));
            chapterLink.parentElement.after(container);
        }
    });
})();
