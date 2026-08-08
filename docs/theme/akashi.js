/* global path_to_root */

(function () {
    "use strict";

    // mdBook only supplies headings for the active page. Keep this outline synchronized with public h2/h3 headings.
    const headingsByChapter = {
        "index.html": [
            { id: "see-it-work", title: "See It Work" },
            { id: "one-corpus-several-uses", title: "One Corpus, Several Uses" },
            { id: "choose-your-next-step", title: "Choose Your Next Step" },
            { id: "project-status", title: "Project Status" },
        ],
        "quick-start.html": [
            { id: "1-install-akashi-and-phpunit", title: "1. Install Akashi and PHPUnit" },
            { id: "2-write-an-example", title: "2. Write an Example" },
            { id: "3-connect-the-document-to-phpunit", title: "3. Connect the Document to PHPUnit" },
            { id: "4-run-it", title: "4. Run It" },
            { id: "5-break-it-deliberately", title: "5. Break It Deliberately" },
            { id: "where-next", title: "Where Next?" },
        ],
        "using/authoring.html": [
            { id: "build-a-corpus", title: "Build a Corpus" },
            { id: "write-php-fences", title: "Write PHP Fences" },
            { id: "labels-and-phpunit-data-sets", title: "Labels and PHPUnit Data Sets" },
            { id: "add-a-stable-marker", title: "Add a Stable Marker" },
            { id: "add-a-runtime-directive", title: "Add a Runtime Directive" },
        ],
        "using/phpunit.html": [
            { id: "connect-a-corpus", title: "Connect a Corpus" },
            { id: "what-in-process-execution-does", title: "What In-Process Execution Does" },
            { id: "assertion-behavior", title: "Assertion Behavior" },
            { id: "skips-and-failures", title: "Skips and Failures" },
        ],
        "using/phpstan.html": [
            { id: "express-an-expected-diagnostic", title: "Express an Expected Diagnostic" },
            { id: "select-relevant-examples", title: "Select Relevant Examples" },
            { id: "connect-a-ruletestcase", title: "Connect a RuleTestCase" },
            { id: "analysis-lifecycle-and-trust", title: "Analysis Lifecycle and Trust" },
        ],
        "using/separate-process.html": [
            { id: "choose-it-for-one-example", title: "Choose It for One Example" },
            { id: "make-it-the-default", title: "Make It the Default" },
            { id: "child-process-boundary", title: "Child-Process Boundary" },
        ],
        "using/extracting.html": [
            { id: "mark-the-example", title: "Mark the Example" },
            { id: "extract-it", title: "Extract It" },
            { id: "select-it-in-php", title: "Select It in PHP" },
        ],
        "guides/test-documentation.html": [
            { id: "define-the-source-set", title: "Define the Source Set" },
            { id: "use-it-in-phpunit", title: "Use It in PHPUnit" },
            { id: "keep-the-set-deliberate", title: "Keep the Set Deliberate" },
        ],
        "guides/reuse-runtime-phpstan.html": [
            { id: "define-a-project-corpus", title: "Define a Project Corpus" },
            { id: "execute-it-with-phpunit", title: "Execute It with PHPUnit" },
            { id: "analyze-a-relevant-subcorpus", title: "Analyze a Relevant Subcorpus" },
            { id: "decide-which-workflow-sees-an-example", title: "Decide Which Workflow Sees an Example" },
        ],
        "guides/diagnosing-failures.html": [
            { id: "discovery-and-metadata-failures", title: "Discovery and Metadata Failures" },
            { id: "parse-and-transform-failures", title: "Parse and Transform Failures" },
            { id: "runtime-failures", title: "Runtime Failures" },
            { id: "phpstan-failures", title: "PHPStan Failures" },
            { id: "temporary-locations", title: "Temporary Locations" },
        ],
        "reference/configuration.html": [
            { id: "markdown-sources", title: "Markdown Sources" },
            { id: "runtime-configuration", title: "Runtime Configuration" },
            { id: "phpstan-configuration", title: "PHPStan Configuration" },
        ],
        "reference/directives.html": [
            { id: "association-rules", title: "Association Rules" },
            { id: "runtime-semantics", title: "Runtime Semantics" },
            { id: "not-implemented", title: "Not Implemented" },
        ],
        "reference/cli.html": [
            { id: "usage", title: "Usage" },
            { id: "exit-statuses", title: "Exit Statuses" },
        ],
        "reference/api.html": [
            { id: "source-and-corpus", title: "Source and Corpus" },
            { id: "phpunit-runtime", title: "PHPUnit Runtime" },
            { id: "phpstan", title: "PHPStan" },
            { id: "exceptions", title: "Exceptions" },
            { id: "optional-dependencies", title: "Optional Dependencies" },
        ],
        "reference/compatibility.html": [
            { id: "supported-platforms-and-integrations", title: "Supported Platforms and Integrations" },
            { id: "authoring-boundary", title: "Authoring Boundary" },
            { id: "in-process-execution-model", title: "In-Process Execution Model" },
            { id: "separate-process-boundary", title: "Separate-Process Boundary" },
            { id: "assertion-and-source-location-boundary", title: "Assertion and Source-Location Boundary" },
            { id: "phpstan-boundary", title: "PHPStan Boundary" },
            { id: "paratest-and-platform-notes", title: "ParaTest and Platform Notes" },
            { id: "recorded-consumer-acceptance", title: "Recorded Consumer Acceptance" },
        ],
        "project/architecture.html": [
            { id: "example-lifecycle", title: "Example Lifecycle" },
            { id: "source-discovery", title: "Source Discovery" },
            { id: "canonical-example-model", title: "Canonical Example Model" },
            { id: "source-locations-and-prepared-code", title: "Source Locations and Prepared Code" },
            { id: "in-process-transformation", title: "In-Process Transformation" },
            {
                id: "execution-backends",
                title: "Execution Backends",
                children: [
                    { id: "in-process", title: "In process" },
                    { id: "separate-process", title: "Separate process" },
                ],
            },
            { id: "verification-and-integrations", title: "Verification and Integrations" },
            { id: "dependency-boundaries", title: "Dependency Boundaries" },
            { id: "current-and-deferred-architecture", title: "Current and Deferred Architecture" },
        ],
        "project/roadmap.html": [
            { id: "markdown-mvp-acceptance", title: "Markdown MVP Acceptance" },
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
