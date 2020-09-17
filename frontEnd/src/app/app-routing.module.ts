import { NgModule } from '@angular/core';
import { Routes, RouterModule } from '@angular/router';
import { RegistroComponent } from './registro/registro.component';
import { LoginComponent } from './login/login.component';
import { ActivateAccountComponent } from './activate-account/activate-account.component';
import { SendEmailComponent } from './send-email/send-email.component';
import { HomeComponent } from './home/home.component';
import { GuardianHomeGuard } from './guardian-home.guard';
import { CambiarClaveComponent } from './cambiar-clave/cambiar-clave.component';
import { ResetComponent } from './reset/reset.component';
import { GuardianLoginGuard } from './guardian-login.guard';
import { TareasCompletadasComponent } from './tareas-completadas/tareas-completadas.component';
import { DashboardComponent } from './dashboard/dashboard.component';


const routes: Routes = [
  { path: '', component: LoginComponent, canActivate: [GuardianLoginGuard] },
  { path: 'login', component: LoginComponent, canActivate: [GuardianLoginGuard] },
  { path: 'registro', component: RegistroComponent, canActivate: [GuardianLoginGuard] },
  { path: 'registroFinal', component: SendEmailComponent, canActivate: [GuardianLoginGuard] },
  { path: 'activateAccount/:token', component: ActivateAccountComponent, canActivate: [GuardianLoginGuard] },
  {
    path: 'home', component: HomeComponent, canActivate: [GuardianHomeGuard],
    children: [
      { path: '',  component: DashboardComponent, canActivate: [GuardianHomeGuard] },
      { path: 'completadas', component: TareasCompletadasComponent, canActivate: [GuardianHomeGuard] }
    ],
  },
  { path: 'cambiarClave', component: CambiarClaveComponent, canActivate: [GuardianLoginGuard] },
  { path: 'reset/:token', component: ResetComponent, canActivate: [GuardianLoginGuard] },
  { path: '**', redirectTo: '' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
