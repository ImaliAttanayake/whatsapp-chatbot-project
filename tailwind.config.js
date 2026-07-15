import defaultTheme from "tailwindcss/defaultTheme";
import forms from "@tailwindcss/forms";

export default {
  content: [
    "./resources/views/**/*.blade.php",
    "./resources/js/**/*.js",
  ],
  theme: {
    extend: {
      fontFamily: {
        sans: ["Figtree", ...defaultTheme.fontFamily.sans],
      },
      colors: {
        slt: {
          primary: "#2258a7",
          "primary-600": "#1c4b8f",
          "primary-700": "#163e77",
          info: "#46b6ef",
          accent: "#5fb545",
          white: "#ffffff",
          ink: "#0c1b2a",
          muted: "#6b7a8a",
          border: "#e6eef8",
          // Aliases for compatibility
          blue: "#2258a7",
          sky: "#46b6ef",
          green: "#5fb545",
        },
      },
      borderRadius: {
        lg: "16px",
        md: "12px",
      },
      boxShadow: {
        slt: "0 12px 26px rgba(0,0,0,.10)",
      },
    },
  },
  plugins: [forms],
};
