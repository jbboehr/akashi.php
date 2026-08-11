# Heliogenesis integration

Akashi's mdBook mounts the optional Heliogenesis browser integration from Doctrine of the Second Sun. The trigger in the
mdBook toolbar starts a temporary **Dawning of the Second Sun** event; ordinary documentation behavior remains the
default.

The upstream runtime is copied verbatim into `docs/pages/assets/heliogenesis/`. Committing those assets keeps mdBook,
GitHub Pages, and the Nix documentation derivation independent of a populated `vendor/` directory. The copied notice
records the Doctrine revision and preserves both the Doctrine and Three.js license texts. Akashi-specific mounting and
shell selection remain in `docs/theme/akashi.js` and `docs/theme/akashi.css` rather than modifying the upstream runtime.
The integration lights the page wrapper and navigation chrome but deliberately leaves the article unmarked so the
reader's selected mdBook theme retains its content colors throughout the event. The unmarked article also supplies no
elements to Heliogenesis's optional document-tomography effect; the environmental animation remains active.

The integration preserves the upstream lifecycle boundaries:

- mounting does not allocate a WebGL context;
- hover, keyboard focus, or activation prepares the renderer;
- reduced-motion users receive the upstream static treatment;
- initialization failure removes or disables the optional effect without disabling the documentation;
- the controller restores the normal document state after the event.

`HeliogenesisAssetsTest` verifies that the public runtime remains byte-for-byte identical to the Composer-installed copy
and that the provenance notice names the locked Doctrine revision. When the Doctrine pin advances, review the upstream
Heliogenesis changes before copying the runtime again; do not modify a copied file to hide drift. If Akashi later
replaces Heliogenesis, remove the public runtime, toolbar mount, shell-lighting hooks, provenance notice, and drift test
together.
