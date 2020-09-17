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
import { HeaderComponent } from './header/header.component';
import { ResetComponent } from './reset/reset.component';
import { DashboardComponent } from './dashboard/dashboard.component';
import { NotaComponent } from './nota/nota.component';
import { SortablejsModule } from 'ngx-sortablejs';
/* import { MaterialModule } from '@angular/material'; */
import { MatDatepickerModule } from '@angular/material/datepicker';
import { MatCalendar } from '@angular/material/datepicker';
import { MatMomentDateModule, MomentDateModule } from '@angular/material-moment-adapter';
import { BrowserAnimationsModule } from '@angular/platform-browser/animations';
import { TareasCompletadasComponent } from './tareas-completadas/tareas-completadas.component';

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
    CambiarClaveComponent,
    HeaderComponent,
    ResetComponent,
    DashboardComponent,
    NotaComponent,
    TareasCompletadasComponent
  ],
  imports: [
    BrowserModule,
    AppRoutingModule,
    FormsModule,
    ReactiveFormsModule,
    HttpClientModule,
    SortablejsModule.forRoot({ handle: '.draggableIcon', animation: 150 }),
    /* MaterialModule.forRoot(), */
    MatDatepickerModule,
    MatMomentDateModule,
    MomentDateModule,
    BrowserAnimationsModule
    /*  SortablejsModule.forRoot({
       animation: 200,
     }) */
  ],
  providers: [
    RegistroService,
    LoginService

  ],
  bootstrap: [AppComponent]
})
export class AppModule { }
