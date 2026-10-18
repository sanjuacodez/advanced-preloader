# Icon source

`icon.html` draws the animated WordPress.org icon in SVG. `window.render('P', t)`
draws the frame at time `t` in seconds; the first frame is the still design.

Export (needs Node with playwright-core and Chrome, Python with Pillow, and gifsicle):
render frames of the `#P` element at 256 and 128 px, combine them with one shared
palette at 40 ms per frame, then optimise with `gifsicle -O3`. Save as
`.wordpress-org/icon-128x128.gif` and `.wordpress-org/icon-256x256.gif`.
The exact scripts are in the Likenook repository under `bin/brand/`.
