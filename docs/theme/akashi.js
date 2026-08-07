/* global path_to_root */

(function () {
    "use strict";

    // mdBook only supplies headings for the active page. Keep this outline synchronized with public h2/h3 headings.
    const headingsByChapter = {
        "index.html": [
            { id: "status", title: "Status" },
            { id: "trust-and-safety", title: "Trust and Safety" },
            { id: "assertion-behavior", title: "Assertion Behavior" },
            { id: "start-here", title: "Start Here" },
        ],
        "getting-started.html": [
            { id: "installation", title: "Installation" },
            { id: "verify-the-command", title: "Verify the Command" },
            { id: "run-examples-with-phpunit", title: "Run Examples with PHPUnit" },
            { id: "verify-examples-with-phpstan", title: "Verify Examples with PHPStan" },
            { id: "next-steps", title: "Next Steps" },
            { id: "development", title: "Development" },
        ],
        "authoring-markdown.html": [
            { id: "build-a-corpus", title: "Build a Corpus" },
            { id: "php-fences", title: "PHP Fences" },
            { id: "explicit-markers", title: "Explicit Markers" },
            { id: "execution-directives", title: "Execution Directives" },
        ],
        "reference/cli.html": [
            { id: "usage", title: "Usage" },
            { id: "exit-statuses", title: "Exit Statuses" },
        ],
        "reference/runtime.html": [
            { id: "phpunit-composition", title: "PHPUnit Composition" },
            { id: "runtime-configuration", title: "Runtime Configuration" },
            {
                id: "backend-behavior",
                title: "Backend Behavior",
                children: [
                    { id: "in-process-execution", title: "In-process Execution" },
                    { id: "separate-process-execution", title: "Separate-process Execution" },
                ],
            },
            { id: "phpunit-reporting", title: "PHPUnit Reporting" },
        ],
        "reference/phpstan.html": [
            { id: "configure-relevant-examples", title: "Configure Relevant Examples" },
            { id: "diagnostic-expectations", title: "Diagnostic Expectations" },
            { id: "analysis-lifecycle", title: "Analysis Lifecycle" },
        ],
        "compatibility.html": [
            { id: "supported-integrations", title: "Supported Integrations" },
            { id: "current-authoring-boundary", title: "Current Authoring Boundary" },
            { id: "runtime-boundary", title: "Runtime Boundary" },
            { id: "phpstan-boundary", title: "PHPStan Boundary" },
            { id: "platform-notes", title: "Platform Notes" },
            { id: "migration-status", title: "Migration Status" },
        ],
        "roadmap.html": [
            { id: "complete-the-markdown-mvp", title: "Complete the Markdown MVP" },
            { id: "phpdoc-example-maintainability", title: "PHPDoc Example Maintainability" },
            { id: "runtime-and-verification", title: "Runtime and Verification" },
            { id: "comparative-review", title: "Comparative Review" },
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

            if (heading.children) {
                item.classList.add("expanded");
                item.append(createHeadingList(pageUrl, heading.children));
            }

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
