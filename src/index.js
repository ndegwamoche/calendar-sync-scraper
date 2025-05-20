import React from "react";
import { createRoot } from 'react-dom/client';
import App from "./App";

const rootElement = document.getElementById("calendar-sync-scraper-root");

if (rootElement) {
    const root = createRoot(rootElement);
    root.render(<App />);
}
