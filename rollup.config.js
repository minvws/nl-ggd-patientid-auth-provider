import commonjs from "@rollup/plugin-commonjs";
import json from "@rollup/plugin-json";
import alias from "@rollup/plugin-alias";
import resolve from "@rollup/plugin-node-resolve";
import analyze from "rollup-plugin-analyzer";
import { terser } from "rollup-plugin-terser";

const production = process.env.NODE_ENV !== "development";

const polyfills = {
  path: "path-browserify",
};

export default {
  input: ["resources/js/app.js"],
  output: {
    file: "public/js/app.js",
    format: "iife",
    name: "app",
    sourcemap: true,
    inlineDynamicImports: true,
    // needed to placate regenerator -_-
    strict: false,
  },
  plugins: [
    commonjs(),
    json(),
    alias({ entries: polyfills }),
    resolve({ preferBuiltins: false, browser: true }),
    analyze({ summaryOnly: true }),
    production && terser({ mangle: false }),
  ],
};
