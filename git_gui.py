import os
import tkinter as tk
from tkinter import filedialog, messagebox
from git import Repo, GitCommandError
import paramiko

# Caminho do repositório local
REPO_PATH = os.path.abspath(os.path.dirname(__file__))
CRED_FILE = os.path.join(REPO_PATH, 'git_credentials.txt')

class GitGUI(tk.Tk):
    def __init__(self):
        super().__init__()
        self.title('Git GUI')
        self.repo = None
        self.create_widgets()
        self.load_credentials()

    def git_pull(self):
        try:
            if self.repo is None:
                self.repo = Repo(REPO_PATH)
            origin = self.repo.remotes.origin
            origin.pull()
            messagebox.showinfo('Sucesso', 'Pull realizado com sucesso!')
        except GitCommandError as e:
            messagebox.showerror('Erro Git', str(e))
        except Exception as e:
            messagebox.showerror('Erro', str(e))

    def ssh_update_server(self):
        host = self.ssh_host_entry.get().strip()
        user = self.ssh_user_entry.get().strip()
        path = self.ssh_path_entry.get().strip()
        key_path = self.ssh_key_entry.get().strip()
        if not host or not user or not path:
            messagebox.showerror('Erro', 'Preencha host, usuário e caminho do projeto remoto.')
            return
    def create_widgets(self):
        frame = tk.Frame(self, bg='#f4f6fa', padx=15, pady=10)
        frame.pack(expand=True)

        title = tk.Label(frame, text='MTechServiços - GitHub Commit', font=('Segoe UI', 14, 'bold'), bg='#f4f6fa', fg='#2d3e50')
        title.grid(row=0, column=0, columnspan=2, pady=(0, 10), sticky='n')

        # Detecta se o remote é SSH
        try:
            self.repo = Repo(REPO_PATH)
            origin_url = self.repo.remotes.origin.url
            is_ssh = origin_url.startswith('git@')
        except GitCommandError as e:
            messagebox.showerror('Erro Git', f'Repositório Git não encontrado ou inválido:\n{e}')
            self.destroy()
            return
        except Exception:
            is_ssh = False

        self.is_ssh = is_ssh

        next_row = 1

        # Campos SSH remoto
        tk.Label(frame, text='Host/IP do servidor:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
        self.ssh_host_entry = tk.Entry(frame, width=28, font=('Segoe UI', 10))
        self.ssh_host_entry.grid(row=next_row, column=1, padx=5, pady=4)
        next_row += 1

        tk.Label(frame, text='Usuário SSH:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
        self.ssh_user_entry = tk.Entry(frame, width=28, font=('Segoe UI', 10))
        self.ssh_user_entry.grid(row=next_row, column=1, padx=5, pady=4)
        next_row += 1

        tk.Label(frame, text='Caminho do projeto remoto:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
        self.ssh_path_entry = tk.Entry(frame, width=28, font=('Segoe UI', 10))
        self.ssh_path_entry.grid(row=next_row, column=1, padx=5, pady=4)
        next_row += 1

        tk.Label(frame, text='Chave privada (opcional):', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
        self.ssh_key_entry = tk.Entry(frame, width=28, font=('Segoe UI', 10))
        self.ssh_key_entry.grid(row=next_row, column=1, padx=5, pady=4)
        next_row += 1

        if not is_ssh:
            tk.Label(frame, text='Usuário do GitHub:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
            self.user_entry = tk.Entry(frame, width=22, font=('Segoe UI', 10))
            self.user_entry.grid(row=next_row, column=1, padx=5, pady=4)
            next_row += 1

            tk.Label(frame, text='Token/Senha do GitHub:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=4)
            self.token_entry = tk.Entry(frame, show='*', width=22, font=('Segoe UI', 10))
            self.token_entry.grid(row=next_row, column=1, padx=5, pady=4)
            next_row += 1

            tk.Button(frame, text='Salvar Credenciais', font=('Segoe UI', 10), bg='#4caf50', fg='white', command=self.save_credentials, width=14, height=1).grid(row=next_row, column=0, columnspan=2, pady=(6, 10))
            next_row += 1

        tk.Button(frame, text='Selecionar Arquivo', font=('Segoe UI', 10), bg='#2196f3', fg='white', command=self.select_file, width=16, height=1).grid(row=next_row, column=0, columnspan=2, pady=6)
        next_row += 1

        tk.Label(frame, text='Mensagem do Commit:', font=('Segoe UI', 11), bg='#f4f6fa').grid(row=next_row, column=0, sticky='e', padx=5, pady=8)
        self.commit_entry = tk.Entry(frame, width=28, font=('Segoe UI', 10))
        self.commit_entry.grid(row=next_row, column=1, padx=5, pady=8)
        next_row += 1

        tk.Button(frame, text='Commit e Push', font=('Segoe UI', 11, 'bold'), bg='#ff9800', fg='white', command=self.commit_and_push, width=18, height=1).grid(row=next_row, column=0, columnspan=2, pady=(12, 6))
        next_row += 1

        tk.Button(frame, text='Baixar do servidor', font=('Segoe UI', 10), bg='#607d8b', fg='white', command=self.git_pull, width=16, height=1).grid(row=next_row, column=0, columnspan=2, pady=(6, 6))
        next_row += 1

        tk.Button(frame, text='Atualizar servidor SSH', font=('Segoe UI', 10), bg='#795548', fg='white', command=self.ssh_update_server, width=16, height=1).grid(row=next_row, column=0, columnspan=2, pady=(6, 6))
        next_row += 1

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
            self.repo.git.add(A=True)
            self.repo.index.commit(commit_msg)
            origin = self.repo.remotes.origin
            if not self.is_ssh:
                user = self.user_entry.get()
                token = self.token_entry.get()
                if 'github.com' in origin.url:
                    url_with_auth = origin.url.replace('https://', f'https://{user}:{token}@')
                    origin.set_url(url_with_auth)
            origin.push()
            messagebox.showinfo('Sucesso', 'Commit e push realizados com sucesso!')
        except GitCommandError as e:
            messagebox.showerror('Erro Git', str(e))
        except Exception as e:
            messagebox.showerror('Erro', str(e))

if __name__ == '__main__':
    try:
        import git
    except ImportError:
        import tkinter as tk
        from tkinter import messagebox
        root = tk.Tk()
        root.withdraw()
        messagebox.showerror('Dependência', 'Instale o pacote GitPython: pip install GitPython')
        root.destroy()
    else:
        app = GitGUI()
        app.mainloop()
