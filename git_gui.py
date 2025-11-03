import os
import tkinter as tk
from tkinter import filedialog, messagebox
from git import Repo, GitCommandError

# Caminho do repositório local
REPO_PATH = os.path.abspath(os.path.dirname(__file__))
CRED_FILE = os.path.join(REPO_PATH, 'git_credentials.txt')

class GitGUI(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('MTechServiços - GitHub Commit')
        self.geometry('800x500')
        self.resizable(False, False)
        self.configure(bg='#f4f6fa')
        self.create_widgets()
        self.repo = None
        if hasattr(self, 'user_entry') and hasattr(self, 'token_entry'):
            self.load_credentials()

    def create_widgets(self):
        # Frame central
        frame = tk.Frame(self, bg='#f4f6fa')
        frame.place(relx=0.5, rely=0.5, anchor='center')

        title = tk.Label(frame, text='MTechServiços - GitHub Commit', font=('Segoe UI', 22, 'bold'), bg='#f4f6fa', fg='#2d3e50')
        title.grid(row=0, column=0, columnspan=2, pady=(10, 30))

        # Detecta se o remote é SSH
        try:
            repo = Repo(REPO_PATH)
            origin_url = repo.remotes.origin.url
            is_ssh = origin_url.startswith('git@')
        except Exception:
            is_ssh = False

        self.is_ssh = is_ssh

        if not is_ssh:
            tk.Label(frame, text='Usuário do GitHub:', font=('Segoe UI', 14), bg='#f4f6fa').grid(row=1, column=0, sticky='e', padx=10, pady=10)
            self.user_entry = tk.Entry(frame, width=35, font=('Segoe UI', 13))
            self.user_entry.grid(row=1, column=1, padx=10, pady=10)

            tk.Label(frame, text='Token/Senha do GitHub:', font=('Segoe UI', 14), bg='#f4f6fa').grid(row=2, column=0, sticky='e', padx=10, pady=10)
            self.token_entry = tk.Entry(frame, show='*', width=35, font=('Segoe UI', 13))
            self.token_entry.grid(row=2, column=1, padx=10, pady=10)

            tk.Button(frame, text='Salvar Credenciais', font=('Segoe UI', 12), bg='#4caf50', fg='white', command=self.save_credentials, width=20, height=1).grid(row=3, column=0, columnspan=2, pady=(10, 20))
            row_offset = 4
        else:
            row_offset = 1

        tk.Button(frame, text='Selecionar Arquivo para Upload', font=('Segoe UI', 12), bg='#2196f3', fg='white', command=self.select_file, width=25, height=1).grid(row=row_offset, column=0, columnspan=2, pady=10)

        tk.Label(frame, text='Mensagem do Commit:', font=('Segoe UI', 14), bg='#f4f6fa').grid(row=row_offset+1, column=0, sticky='e', padx=10, pady=10)
        self.commit_entry = tk.Entry(frame, width=35, font=('Segoe UI', 13))
        self.commit_entry.grid(row=row_offset+1, column=1, padx=10, pady=10)

        tk.Button(frame, text='Commit e Push', font=('Segoe UI', 13, 'bold'), bg='#ff9800', fg='white', command=self.commit_and_push, width=20, height=2).grid(row=row_offset+2, column=0, columnspan=2, pady=(30, 10))

    def save_credentials(self):
        user = self.user_entry.get()
        token = self.token_entry.get()
        with open(CRED_FILE, 'w') as f:
            f.write(f'{user}\n{token}')
        messagebox.showinfo('Sucesso', 'Credenciais salvas!')

    def load_credentials(self):
        if os.path.exists(CRED_FILE):
            with open(CRED_FILE, 'r') as f:
                lines = f.read().splitlines()
                if len(lines) >= 2:
                    self.user_entry.insert(0, lines[0])
                    self.token_entry.insert(0, lines[1])

    def select_file(self):
        file_path = filedialog.askopenfilename(initialdir=REPO_PATH)
        if file_path:
            dest_path = os.path.join(REPO_PATH, os.path.basename(file_path))
            if file_path != dest_path:
                try:
                    with open(file_path, 'rb') as src, open(dest_path, 'wb') as dst:
                        dst.write(src.read())
                    messagebox.showinfo('Arquivo', f'Arquivo copiado para o projeto: {os.path.basename(file_path)}')
                except Exception as e:
                    messagebox.showerror('Erro', f'Erro ao copiar arquivo: {e}')
            else:
                messagebox.showinfo('Arquivo', 'Arquivo já está na pasta do projeto.')

    def commit_and_push(self):
        commit_msg = self.commit_entry.get()
        if not commit_msg:
            messagebox.showerror('Erro', 'Digite uma mensagem de commit.')
            return
        try:
            if self.repo is None:
                self.repo = Repo(REPO_PATH)
            self.repo.git.add(A=True)
            self.repo.index.commit(commit_msg)
            origin_url = self.repo.remotes.origin.url
            if not self.is_ssh:
                user = self.user_entry.get()
                token = self.token_entry.get()
                if 'github.com' in origin_url:
                    url_with_auth = origin_url.replace('https://', f'https://{user}:{token}@')
                    self.repo.remotes.origin.set_url(url_with_auth)
            self.repo.remotes.origin.push()
            messagebox.showinfo('Sucesso', 'Commit e push realizados com sucesso!')
        except GitCommandError as e:
            messagebox.showerror('Erro Git', str(e))
        except Exception as e:
            messagebox.showerror('Erro', str(e))

if __name__ == '__main__':
    import sys
    import traceback
    try:
        import git
        app = GitGUI()
        app.mainloop()
    except ImportError:
        root = tk.Tk()
        root.withdraw()
        messagebox.showerror('Dependência', 'Instale o pacote GitPython: pip install GitPython')
    except Exception as e:
        root = tk.Tk()
        root.withdraw()
        tb = traceback.format_exc()
        messagebox.showerror('Erro ao iniciar', f'{e}\n\n{tb}')
