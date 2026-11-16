<?php
// index.php - Rangka Utama Gallery (Single Page Application)
?>
<!DOCTYPE html>
<html lang="id" class="scroll-smooth">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Galeri OSIS Medikacom</title>

    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/Flip.min.js"></script>
    <script src="https://unpkg.com/@phosphor-icons/web"></script>

    <style>
        :root {
            --bg-color: #000000;
        }
        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-color);
            color: #ffffff;
            overflow-x: hidden;
            -webkit-font-smoothing: antialiased;
        }
        .fixed-bg {
            position: fixed; top: -5%; left: -5%; width: 110vw; height: 110vh;
            background-image: url('DSC06963.webp'); /* Ganti dengan path bg default lu */
            background-size: cover; background-position: center; background-repeat: no-repeat;
            filter: blur(16px) brightness(0.67); z-index: -10; pointer-events: none;
        }
        
        .liquid-glass {
            background: rgba(255, 255, 255, 0.05);
            backdrop-filter: blur(24px) saturate(180%);
            -webkit-backdrop-filter: blur(24px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 24px 48px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }
        .bento-box {
            transition: transform 0.4s cubic-bezier(0.25, 1, 0.5, 1), background 0.4s ease, border-color 0.4s ease, box-shadow 0.4s ease;
            will-change: transform;
        }
        @media (min-width: 768px) {
            .bento-box:hover {
                transform: translateY(-8px) scale(1.02);
                background: rgba(255, 255, 255, 0.08);
                border-color: rgba(255, 255, 255, 0.3);
                box-shadow: -10px 30px 60px rgba(0,0,0,0.5);
            }
        }
        
        #app-content { transition: opacity 0.4s ease, transform 0.4s ease; }
        .page-loading { opacity: 0; transform: translateY(20px); pointer-events: none; }
        .page-loaded { opacity: 1; transform: translateY(0); }
        
        .modal-backdrop {
            background: rgba(0,0,0,0.85); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px);
        }
    </style>
</head>
<body class="min-h-screen w-full relative">

    <div class="fixed-bg"></div>

    <div id="loader-screen" class="fixed inset-0 z-[100] flex items-center justify-center flex-col pointer-events-none transition-colors duration-700 bg-black/80">
        <div class="flex items-center justify-center relative w-full h-32">
            <h1 class="text-white text-2xl font-bold tracking-widest animate-pulse">MEMUAT GALERI...</h1>
        </div>
    </div>
    <div id="big-bang-flash" class="fixed left-1/2 -translate-x-1/2 w-2 h-2 rounded-full bg-white z-[95] pointer-events-none opacity-0" style="top: 50vh; box-shadow: 0 0 100px 50px rgba(255,255,255,0.8);"></div>

    <!-- Dynamic Island Navbar -->
    <div id="dynamic-island" class="liquid-glass fixed left-1/2 -translate-x-1/2 z-[90] overflow-hidden"
         style="top: 40px; width: min(340px, 92vw); height: 48px; border-radius: 24px; opacity: 0;">
        <div class="absolute inset-0 w-full h-full flex items-center justify-between px-5">
            <div class="flex items-center gap-2"><span id="island-clock" class="text-white/90 text-sm font-bold font-mono">00:00</span></div>
            <a href="?beranda" class="flex items-center gap-2 absolute left-1/2 -translate-x-1/2 cursor-pointer hover:scale-105 transition">
                <div class="w-2 h-2 rounded-full bg-blue-400 animate-pulse shadow-[0_0_8px_rgba(96,165,250,0.8)]"></div>
                <span class="text-white/90 text-xs font-semibold tracking-widest uppercase">Galeri OSIS</span>
            </a>
            <div class="flex items-center text-white/70 hover:text-white cursor-pointer" onclick="history.back()"><i class="ph ph-arrow-u-up-left text-lg"></i></div>
        </div>
    </div>

    <main id="app-content" class="relative z-10 pt-32 sm:pt-40 pb-16 min-h-screen flex flex-col page-loading">
        <!-- SPA INJECTS CONTENT HERE -->
    </main>

    <!-- Footer -->
    <footer class="max-w-7xl mx-auto w-full px-5 mt-10 mb-10 relative z-10 text-center">
        <p class="text-white/40 text-xs tracking-wide uppercase flex items-center justify-center gap-1.5 flex-wrap">
            &copy; <?= date('Y') ?> OSIS Medikacom. <a href="https://minex.gt.tc" target="_blank" class="text-white/80 font-bold hover:text-purple-400 transition">Minex <i class="ph-fill ph-lightning"></i></a>
        </p>
    </footer>

    <script>
        gsap.registerPlugin(ScrollTrigger);

        // Jam Digital
        setInterval(() => { const d = new Date(); document.getElementById('island-clock').textContent = d.getHours().toString().padStart(2,'0') + ':' + d.getMinutes().toString().padStart(2,'0'); }, 1000);

        // SPA Router
        const appContent = document.getElementById('app-content');
        
        async function loadContent(url, isPopState = false) {
            appContent.classList.remove('page-loaded');
            appContent.classList.add('page-loading');
            
            try {
                const res = await fetch(url);
                if (!res.ok) throw new Error('Not found');
                const html = await res.text();
                
                setTimeout(() => {
                    appContent.innerHTML = html;
                    
                    if(!isPopState) {
                        const q = new URLSearchParams(url.split('?')[1]).toString() ? `?${url.split('?')[1]}` : '?beranda';
                        history.pushState({ path: q }, '', q);
                    }
                    
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                    initPageScripts();
                    
                    appContent.classList.remove('page-loading');
                    appContent.classList.add('page-loaded');
                }, 400); // Wait for fade out
            } catch (err) {
                appContent.innerHTML = '<div class="text-center py-20 text-white/50">Halaman tidak ditemukan / Error jaringan.</div>';
                appContent.classList.remove('page-loading'); appContent.classList.add('page-loaded');
            }
        }

        // Init Interactions
        function initPageScripts() {
            ScrollTrigger.getAll().forEach(st => st.kill());
            
            // Animasi Kartu GSAP
            gsap.utils.toArray('.animate-card').forEach((card, i) => {
                gsap.from(card, {
                    scrollTrigger: { trigger: card, start: "top 95%" },
                    y: 50, opacity: 0, duration: 0.6, ease: "back.out(1.2)", delay: (i%10) * 0.05
                });
            });

            // Likes Logic
            document.querySelectorAll('.btn-like').forEach(btn => {
                btn.addEventListener('click', async (e) => {
                    e.preventDefault();
                    const album = btn.dataset.album, photo = btn.dataset.photo;
                    const res = await fetch('site/like.php', {
                        method: 'POST', headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: `album=${encodeURIComponent(album)}&photo=${encodeURIComponent(photo)}`
                    });
                    const data = await res.json();
                    if(data.success) {
                        btn.querySelector('.like-count').innerText = data.total;
                        btn.classList.add('text-red-500');
                        btn.querySelector('i').classList.replace('ph','ph-fill');
                    } else alert(data.message);
                });
            });

            // Preview Modal (Memory Optimization)
            document.querySelectorAll('.btn-preview').forEach(btn => {
                btn.addEventListener('click', (e) => {
                    e.preventDefault();
                    const src = btn.dataset.src;
                    
                    const backdrop = document.createElement('div');
                    backdrop.className = 'fixed inset-0 z-[100] flex items-center justify-center p-4 modal-backdrop opacity-0 transition-opacity duration-300';
                    
                    const img = document.createElement('img');
                    img.src = src;
                    img.className = 'max-w-full max-h-full object-contain rounded-xl shadow-2xl scale-95 transition-transform duration-300';
                    
                    const close = document.createElement('button');
                    close.className = 'absolute top-5 right-5 w-10 h-10 rounded-full bg-white/10 hover:bg-white/20 text-white flex items-center justify-center backdrop-blur-md';
                    close.innerHTML = '<i class="ph ph-x text-xl"></i>';

                    backdrop.appendChild(img); backdrop.appendChild(close); document.body.appendChild(backdrop);
                    
                    requestAnimationFrame(() => { backdrop.classList.remove('opacity-0'); img.classList.remove('scale-95'); });

                    const destroyModal = () => {
                        backdrop.classList.add('opacity-0'); img.classList.add('scale-95');
                        setTimeout(() => {
                            img.remove(); // FREE UP RAM!
                            backdrop.remove();
                        }, 300);
                    };
                    close.addEventListener('click', destroyModal);
                    backdrop.addEventListener('click', (e) => { if(e.target === backdrop) destroyModal(); });
                });
            });
        }

        // SPA Navigation bindings
        document.body.addEventListener('click', e => {
            const link = e.target.closest('a[href^="?"]');
            if (link) { e.preventDefault(); loadContent('site/' + (link.getAttribute('href').includes('?album=') ? 'album.php' + link.getAttribute('href') : 'beranda.php')); }
        });
        window.addEventListener('popstate', e => {
            const state = e.state;
            loadContent('site/' + (state && state.path && state.path.includes('?album=') ? 'album.php' + state.path : 'beranda.php'), true);
        });

        window.onload = () => {
            setTimeout(() => {
                const tl = gsap.timeline();
                document.getElementById('loader-screen').style.opacity = 0;
                
                tl.set('#big-bang-flash', { opacity: 1, width: '12px', height: '12px' })
                  .to('#big-bang-flash', { top: '40px', duration: 0.5, ease: "power3.inOut" })
                  .to('#big-bang-flash', { scaleX: 3, scaleY: 1.5, duration: 0.3 })
                  .to('#big-bang-flash', { top: '50vh', scaleX: 1, scaleY: 2.5, duration: 0.3, ease: "power2.in" })
                  .add("bang")
                  .to('#big-bang-flash', { scale: 150, opacity: 0, duration: 0.5 }, "bang")
                  .to('#dynamic-island', { opacity: 1, duration: 0.5 }, "bang")
                  .to('#loader-screen', { display: 'none', duration: 0 });
                
                // Load Initial Page
                const params = new URLSearchParams(window.location.search);
                loadContent('site/' + (params.has('album') ? 'album.php?album=' + params.get('album') : 'beranda.php'));
            }, 1000);
        };
    </script>
</body>
</html>