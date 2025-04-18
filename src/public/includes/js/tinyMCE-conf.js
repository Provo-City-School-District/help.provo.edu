//initialize tinyMCE for for textarea with class tinyMCEtextarea
// var userPref = ''; // Replace this with your actual code to get the user preference

var skin, content_css, content_styles;

if (userPref === "dark") {
  skin = "oxide-dark";
  content_css = "dark";
  content_styles = `
  .mce-content-body [data-mce-selected=inline-boundary] {
    color: #fff;
  }
`;
} else {
  skin = "oxide";
  content_css = "default";
}

tinymce.init({
  selector: ".tinyMCEtextarea",
  toolbar:
    "undo redo restoredraft | bold italic strikethrough | blockquote | paste pastetext removeformat | numlist bullist | code | link unlink | emoticons | codesample | wordcount",
  menubar: false,
  license_key: "gpl",
  paste_as_text: true,
  browser_spellcheck: true,
  contextmenu: false,
  plugins: [
    "autosave",
    "lists",
    "code",
    "link",
    "autolink",
    "wordcount",
    "emoticons",
    "codesample",
  ],
  skin: skin,
  content_style: content_styles,
  paste_data_images: false,
  content_css: content_css,
  link_default_target: "_blank",
  text_patterns: false,
  autosave_interval: "10s",
});
