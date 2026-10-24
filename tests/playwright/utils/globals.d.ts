// Browser globals the specs read inside page.evaluate().
interface TinyMCEEditor {
  getContent: (args?: { format?: string }) => string;
  setContent: (content: string) => void;
}

interface Window {
  tinyMCE: {
    get: (id: string) => TinyMCEEditor | null;
    editors: { [key: string]: unknown };
  };
  xssTriggered?: boolean;
}
