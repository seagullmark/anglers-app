const Ziggy = {"url":"http:\/\/localhost:8000","port":8000,"defaults":{},"routes":{"login":{"uri":"login","methods":["GET","HEAD"]},"login.store":{"uri":"login","methods":["POST"]},"index":{"uri":"\/","methods":["GET","HEAD"]},"user.profile-photo":{"uri":"user\/profile-photo","methods":["GET","HEAD"]},"logout":{"uri":"logout","methods":["POST"]},"user-photos.store":{"uri":"user-photos","methods":["POST"]},"container":{"uri":"container\/{path}","methods":["GET","HEAD"],"parameters":["path"]},"storage.local":{"uri":"storage\/{path}","methods":["GET","HEAD"],"wheres":{"path":".*"},"parameters":["path"]}}};
if (typeof window !== 'undefined' && typeof window.Ziggy !== 'undefined') {
  Object.assign(Ziggy.routes, window.Ziggy.routes);
}
export { Ziggy };
