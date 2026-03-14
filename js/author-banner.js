(() => {
  try {
    const gradientStyle = [
      "font-size:58px",
      "font-weight:800",
      "line-height:1",
      "background:linear-gradient(90deg,#00c6ff,#0072ff,#8e2de2,#ff0080)",
      "-webkit-background-clip:text",
      "background-clip:text",
      "color:transparent",
      "padding:6px 0",
    ].join(";");

    console.log("%cFYX", gradientStyle);
    console.log("Designed by FanYuXuan | Graduation Project 2026");

    const a = "RGVzaWduZWQgYnkg";
    const b = "RmFuWXVYdWFuIHwg";
    const c = "R3JhZHVhdGlvbiBQcm9qZWN0IDIwMjY=";
    const author = atob(a + b + c);
    console.log(author);
  } catch (e) {
    // ignore
  }
})();
