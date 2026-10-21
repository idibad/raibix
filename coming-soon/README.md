# Raibix — Coming Soon Page

An interactive, responsive "Coming Soon" landing page for **Raibix** featuring:
- **Typography:** Strictly "Raibix" in **Poppins Bold** (no image logos).
- **Interactive Physics:** Real-time canvas constellation with cursor magnetic repulsion and shockwave bursts.
- **Micro-interactions:** 3D tilt cards, dynamic countdown timer, and sound effect synth (Web Audio API, zero audio asset downloads needed).
- **VIP Early Access Form:** Email waitlist with celebratory confetti micro-animation and `localStorage` persistence.
- **Easter Egg:** Clicking "Raibix" creates a cosmic particle pulse wave.

---

## ⚡ Option 1: Cloudflare Pages (Recommended — 2 Minutes)
*Since your domain is already on Cloudflare, this is the quickest, most seamless method:*

1. Open your **[Cloudflare Dashboard](https://dash.cloudflare.com/)**.
2. Go to **Workers & Pages** on the left menu, then click **Create application**.
3. Select the **Pages** tab and click **Upload assets**.
4. Project name: Enter `raibix` (or anything you like) and click **Create project**.
5. Drag and drop the `coming-soon` folder (or select `index.html`) and click **Deploy site**.
6. Once deployed:
   - Click **Custom domains** tab in your Pages project.
   - Click **Set up a custom domain**.
   - Type your domain (e.g. `yourdomain.com` or `www.yourdomain.com`).
   - Cloudflare will automatically configure the DNS record and provide free SSL immediately!

---

## 🐙 Option 2: GitHub Pages

If you prefer GitHub Pages:

1. Create a new GitHub repository (e.g., `raibix-coming-soon`).
2. Push the contents of the `coming-soon` folder to the repository root:
   ```bash
   cd coming-soon
   git init
   git add .
   git commit -m "Initial commit: Raibix coming soon page"
   git branch -M main
   git remote add origin https://github.com/<your-username>/<repo-name>.git
   git push -u origin main
   ```
3. In your GitHub repository:
   - Go to **Settings** → **Pages**.
   - Under **Build and deployment** > Branch, select `main` and root `/` and click **Save**.
   - Under **Custom domain**, enter your domain name (e.g., `yourdomain.com`) and click **Save**.
4. In your **Cloudflare DNS**:
   - Add a `CNAME` record:
     - **Name:** `@` (or `www` or subdomain)
     - **Target:** `<your-username>.github.io`
     - **Proxy status:** Proxied (Orange cloud) or DNS only (Gray cloud).
   - If using apex domain (`yourdomain.com`), GitHub also recommends 4 `A` records pointing to GitHub Pages IPs:
     - `185.199.108.153`
     - `185.199.109.153`
     - `185.199.110.153`
     - `185.199.111.153`
