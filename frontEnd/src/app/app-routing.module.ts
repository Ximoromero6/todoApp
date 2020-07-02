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


const routes: Routes = [
  { path: '', component: LoginComponent },
  { path: 'login', component: LoginComponent },
  { path: 'registro', component: RegistroComponent },
  { path: 'registroFinal', component: SendEmailComponent },
  { path: 'activateAccount/:token', component: ActivateAccountComponent },
  { path: 'home', component: HomeComponent, canActivate: [GuardianHomeGuard] },
  { path: 'cambiarClave', component: CambiarClaveComponent },
  { path: 'reset/:token', component: ResetComponent },
  { path: '**', redirectTo: '' }
];

@NgModule({
  imports: [RouterModule.forRoot(routes)],
  exports: [RouterModule]
})
export class AppRoutingModule { }
