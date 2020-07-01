import { BrowserModule } from '@angular/platform-browser';
import { NgModule } from '@angular/core';
import { AppRoutingModule } from './app-routing.module';
import { AppComponent } from './app.component';
import { RegistroComponent } from './registro/registro.component';
import { LoginComponent } from './login/login.component';
import { FormsModule, ReactiveFormsModule } from '@angular/forms';
import { HttpClientModule } from '@angular/common/http';
import { RegistroService } from './registro/registro.service';
import { LoginService } from './login/login.service';
import { ActivateAccountComponent } from './activate-account/activate-account.component';
import { SendEmailComponent } from './send-email/send-email.component';
import { HomeComponent } from './home/home.component';
import { NotificacionesComponent } from './notificaciones/notificaciones.component';
import { LoaderComponent } from './loader/loader.component';
import { CambiarClaveComponent } from './cambiar-clave/cambiar-clave.component';

@NgModule({
  declarations: [
    AppComponent,
    RegistroComponent,
    LoginComponent,
    ActivateAccountComponent,
    SendEmailComponent,
    HomeComponent,
    NotificacionesComponent,
    LoaderComponent,
    CambiarClaveComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule
  ],
  providers: [
    RegistroService,
    LoginService

  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
