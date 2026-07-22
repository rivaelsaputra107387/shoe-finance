<style>
/* Hide left panel by default, show only on login using route check in blade */
#split-left-panel {
    display: block;
    position: fixed;
    top: 0;
    left: 0;
    width: 50%;
    height: 100vh;
    background: #0f172a; /* Slate 900 for Finance theme */
    z-index: 9999;
    padding: 4rem;
    box-sizing: border-box;
    overflow: hidden;
}

#split-left-panel::before {
    content: "";
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle at center, rgba(16, 185, 129, 0.15) 0%, transparent 40%); /* Emerald hint */
    z-index: -1;
}

.split-content {
    display: flex;
    flex-direction: column;
    justify-content: center;
    height: 100%;
    max-width: 500px;
    margin: 0 auto;
}

/* 1. Global Reset for the Auth Page */
html, body {
    background: #ffffff !important;
    margin: 0 !important;
    padding: 0 !important;
    min-height: 100vh !important;
    overflow-x: hidden !important;
}

/* 2. Neutralize the Filament Layout Wrappers */
/* We don't want the outer wrappers to have background or block our view */
.fi-layout, .fi-layout > div {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
}

/* 3. The Magic Move: Force the form container to the right */
/* .fi-simple-main is the container that holds the login form in Filament */
main.fi-simple-main, .fi-simple-main {
    margin-left: 50vw !important;
    width: 50vw !important;
    min-height: 100vh !important;
    background: #ffffff !important;
    display: flex !important;
    flex-direction: column !important;
    align-items: center !important;
    justify-content: center !important;
    box-shadow: none !important;
    border: none !important;
    border-radius: 0 !important;
    padding: 2rem !important;
    box-sizing: border-box !important;
}

/* 4. Ensure the inner form box stays clean */
.fi-simple-main section, .fi-simple-main .fi-simple-main-ctn {
    background: transparent !important;
    box-shadow: none !important;
    border: none !important;
    width: 100% !important;
    max-width: 450px !important;
}

/* Hide default logo */
.fi-logo, .fi-simple-main header > a, .fi-simple-main header img {
    display: none !important; 
}

/* 5. Force all text inside the right panel to be dark and visible */
.fi-simple-main span, 
.fi-simple-main p, 
.fi-simple-main label, 
.fi-simple-main h2, 
.fi-simple-main h3 {
    color: #0f172a !important;
}

/* Fix input wrappers (Filament puts borders on wrappers, not inputs directly) */
.fi-simple-main .fi-input-wrp {
    background: #ffffff !important;
    border: 1px solid #94a3b8 !important;
    border-radius: 8px !important;
    box-shadow: none !important;
    overflow: hidden !important;
}
.fi-simple-main .fi-input-wrp:focus-within {
    border-color: #f59e0b !important;
    outline: 1px solid #f59e0b !important;
}

/* Transparent actual inputs so the wrapper handles the background */
.fi-simple-main input {
    background: transparent !important;
    border: none !important;
    color: #0f172a !important;
    box-shadow: none !important;
}
.fi-simple-main .fi-input-wrp button {
    color: #475569 !important; /* Make the eye icon dark */
}

/* Fix Chrome Autofill grey/yellow background */
.fi-simple-main input:-webkit-autofill,
.fi-simple-main input:-webkit-autofill:hover, 
.fi-simple-main input:-webkit-autofill:focus, 
.fi-simple-main input:-webkit-autofill:active{
    -webkit-box-shadow: 0 0 0 30px white inset !important;
    -webkit-text-fill-color: #0f172a !important;
}

/* Ensure checkbox is visible and keeps its checkmark */
.fi-simple-main input[type="checkbox"] {
    background-color: #ffffff !important;
    border: 2px solid #94a3b8 !important;
    appearance: none !important;
    width: 1.25rem !important;
    height: 1.25rem !important;
    border-radius: 0.25rem !important;
}
.fi-simple-main input[type="checkbox"]:checked {
    background-color: #f59e0b !important;
    border-color: #f59e0b !important;
    background-image: url("data:image/svg+xml,%3csvg viewBox='0 0 16 16' fill='white' xmlns='http://www.w3.org/2000/svg'%3e%3cpath d='M12.207 4.793a1 1 0 010 1.414l-5 5a1 1 0 01-1.414 0l-2-2a1 1 0 011.414-1.414L6.5 9.086l4.293-4.293a1 1 0 011.414 0z'/%3e%3c/svg%3e") !important;
    background-size: 100% 100% !important;
    background-position: center !important;
    background-repeat: no-repeat !important;
}

/* Custom text above "Sign in" */
.fi-simple-main header::before {
    content: "Selamat datang kembali";
    display: block;
    font-size: 32px;
    font-weight: 800;
    color: #0f172a;
    margin-bottom: 8px;
}
/* Hide the original Sign in text */
.fi-simple-main h2 {
    display: none !important;
}
.fi-simple-main header::after {
    content: "Masuk untuk mengelola data jurnal dan pelaporan keuangan perusahaan secara real-time.";
    display: block;
    font-size: 15px;
    color: #64748b;
    margin-top: 8px;
    margin-bottom: 32px;
    line-height: 1.5;
}

/* Button style */
button[type="submit"] {
    background: #f59e0b !important;
    color: white !important;
    border-radius: 8px !important;
    padding: 12px !important;
    font-weight: 700 !important;
    font-size: 16px !important;
    border: none !important;
    box-shadow: none !important;
}
button[type="submit"] * {
    color: white !important;
}
button[type="submit"]:hover {
    background: #d97706 !important;
}

/* Floating cards animation */
@keyframes float1 {
    0% { transform: translateY(0px); }
    50% { transform: translateY(-12px); }
    100% { transform: translateY(0px); }
}
@keyframes float2 {
    0% { transform: translateY(0px); }
    50% { transform: translateY(15px); }
    100% { transform: translateY(0px); }
}
.card-1 { animation: float1 7s ease-in-out infinite; }
.card-2 { animation: float2 8s ease-in-out infinite; }

/* Responsive: hide left panel on mobile */
@media (max-width: 1024px) {
    #split-left-panel {
        display: none !important;
    }
    .fi-layout {
        margin-left: 0 !important;
        width: 100% !important;
    }
}
</style>
