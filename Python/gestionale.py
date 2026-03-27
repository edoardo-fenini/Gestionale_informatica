import tkinter as tk
from tkinter import ttk, messagebox
import requests

API_CLIENTI = "http://localhost/gestionale/api_clienti.php"
API_AGENTI = "http://localhost/gestionale/api_agenti.php"
API_IMPIEGHI = "http://localhost/gestionale/api_impieghi.php"
API_ORDINI = "http://localhost/gestionale/api_ordini.php"

def api_get(url, params=None):
    try:
        r = requests.get(url, params=params, timeout=5)
        r.raise_for_status()
        data = r.json()
        return data if isinstance(data, list) else []
    except Exception as e:
        print("Errore GET:", e)
        return []

def api_post(url, data):
    try:
        r = requests.post(url, json=data, timeout=5)
        r.raise_for_status()
        return r.json()
    except Exception as e:
        print("Errore POST:", e)
        return {"error": "Errore"}

def api_delete(url, item_id):
    try:
        r = requests.delete(url, json=item_id, timeout=5)
        r.raise_for_status()
        return r.json()
    except Exception as e:
        print("Errore DELETE:", e)
        return {"error": "Errore"}

class App:
    def __init__(self, root):
        self.root = root
        self.root.title("Gestionale Completo")
        self.root.geometry("1000x700")
        self.create_tabs()
        self.refresh_all()

    def create_tabs(self):
        self.nb = ttk.Notebook(self.root)
        self.nb.pack(fill="both", expand=True)

        self.tabs = {}
        for t in ["clienti", "agenti", "impieghi", "ordini"]:
            tab = ttk.Frame(self.nb)
            self.nb.add(tab, text=t.capitalize())
            self.build_tab(tab, t)
            self.tabs[t] = tab

    def build_tab(self, tab, tipo):
        frame = ttk.LabelFrame(tab, text="Inserisci / Filtra")
        frame.pack(fill="x", padx=10, pady=10)

        if tipo == "clienti":
            colonne = ["id", "nome", "cognome", "email"]
        elif tipo == "agenti":
            colonne = ["id_agente", "nome", "cognome", "email", "id_impiego"]
        elif tipo == "impieghi":
            colonne = ["id_impiego", "nome_impiego", "ral"]
        elif tipo == "ordini":
            colonne = ["id_ordine", "importo", "id_cliente", "id_agente"]

        entries = {}
        for i, col in enumerate(colonne[1:]):  # non mostriamo ID come entry
            ttk.Label(frame, text=col).grid(row=i, column=0)
            e = ttk.Entry(frame)
            e.grid(row=i, column=1)
            entries[col] = e
        setattr(self, f"{tipo}_entries", entries)

        ttk.Button(frame, text="Aggiungi", command=lambda t=tipo: self.aggiungi(t)).grid(row=len(entries), column=0)
        ttk.Button(frame, text="Filtra", command=lambda t=tipo: self.filtra(t)).grid(row=len(entries), column=1)
        ttk.Button(frame, text="Reset", command=lambda t=tipo: self.refresh_tab(t)).grid(row=len(entries), column=2)
        ttk.Button(frame, text="Elimina", command=lambda t=tipo: self.elimina(t)).grid(row=len(entries), column=3)

        tree = ttk.Treeview(tab, columns=colonne, show="headings")
        for col in colonne:
            tree.heading(col, text=col)
            tree.column(col, width=100, anchor="center")
        tree.pack(fill="both", expand=True, padx=10, pady=10)
        setattr(self, f"{tipo}_tree", tree)
        setattr(self, f"{tipo}_colonne", colonne)

    def refresh_all(self):
        for t in ["clienti", "agenti", "impieghi", "ordini"]:
            self.refresh_tab(t)

    def refresh_tab(self, tipo):
        url = globals()[f"API_{tipo.upper()}"]
        data = api_get(url)
        tree = getattr(self, f"{tipo}_tree")
        colonne = getattr(self, f"{tipo}_colonne")
        tree.delete(*tree.get_children())
        for d in data:
            tree.insert("", "end", values=[d.get(c, "") for c in colonne])

    def filtra(self, tipo):
        url = globals()[f"API_{tipo.upper()}"]
        entries = getattr(self, f"{tipo}_entries")
        params = {k: v.get() for k, v in entries.items() if v.get()}
        data = api_get(url, params)
        tree = getattr(self, f"{tipo}_tree")
        colonne = getattr(self, f"{tipo}_colonne")
        tree.delete(*tree.get_children())
        for d in data:
            tree.insert("", "end", values=[d.get(c, "") for c in colonne])

    def aggiungi(self, tipo):
        url = globals()[f"API_{tipo.upper()}"]
        entries = getattr(self, f"{tipo}_entries")
        data = {k: v.get() for k, v in entries.items() if v.get()}
        res = api_post(url, data)
        if "error" in res:
            messagebox.showerror("Errore", res["error"])
        self.refresh_tab(tipo)

    def elimina(self, tipo):
        tree = getattr(self, f"{tipo}_tree")
        sel = tree.selection()
        if not sel:
            messagebox.showwarning("Errore", "Seleziona un item")
            return
        item = tree.item(sel)
        vals = list(item["values"])
        key = getattr(self, f"{tipo}_colonne")[0]  # prendi la prima colonna (ID)
        res = api_delete(globals()[f"API_{tipo.upper()}"], {key: vals[0]})
        if "error" in res:
            messagebox.showerror("Errore", res["error"])
        self.refresh_tab(tipo)

if __name__ == "__main__":
    root = tk.Tk()
    app = App(root)
    root.mainloop()
