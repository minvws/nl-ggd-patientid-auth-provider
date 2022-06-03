module.exports = {
  plugins: [
    require("autoprefixer"),
    require("cssnano"),
    require("postcss-custom-properties")({ preserve: false }),
  ],
};
